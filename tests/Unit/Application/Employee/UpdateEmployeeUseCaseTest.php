<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Employee;

use Application\Employee\UseCase\UpdateEmployeeInput;
use Application\Employee\UseCase\UpdateEmployeeUseCase;
use Application\Shared\Transaction\TransactionManagerInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Domain\Employee\Entity\Employee;
use Domain\Employee\Exception\DuplicateEmployeeEmailException;
use Domain\Employee\Exception\DuplicateEmployeeNumberException;
use Domain\Employee\Exception\EmployeeNotFoundException;
use Domain\Employee\ValueObject\Department;
use Domain\Employee\ValueObject\EmployeeId;
use Domain\Employee\ValueObject\EmployeeNumber;
use Domain\Employee\ValueObject\EmploymentStatus;
use Domain\Employee\ValueObject\HireDate;
use Domain\Employee\ValueObject\PersonName;
use Domain\Shared\Exception\InvalidValueException;
use Domain\Shared\ValueObject\EmailAddress;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryEmployeeRepository;

final class UpdateEmployeeUseCaseTest extends TestCase
{
    private const CREATED_AT = '2024-04-01 09:00:00';

    private InMemoryEmployeeRepository $employees;

    private UpdateEmployeeUseCase $useCase;

    private Employee $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employees = new InMemoryEmployeeRepository;
        $this->useCase = new UpdateEmployeeUseCase($this->employees, $this->transactionManager());

        $this->target = $this->employee('EMP-0001', 'taro@example.com');
        $this->employees->save($this->target);
    }

    public function test_指定された項目だけを更新する(): void
    {
        $output = $this->useCase->execute(new UpdateEmployeeInput(
            id: $this->target->id()->value(),
            department: '営業部',
        ));

        $this->assertSame('営業部', $output->department);
        $this->assertSame('EMP-0001', $output->employeeNumber, '未指定の項目は変更されない');
        $this->assertSame('山田 太郎', $output->fullName);
        $this->assertSame('taro@example.com', $output->email);
        $this->assertNotSame($output->createdAt, $output->updatedAt, '変更があれば更新日時が進む');

        $saved = $this->employees->findById($this->target->id());
        $this->assertNotNull($saved);
        $this->assertSame('営業部', $saved->department()->value());
    }

    public function test_姓だけを指定しても名は維持される(): void
    {
        $output = $this->useCase->execute(new UpdateEmployeeInput(
            id: $this->target->id()->value(),
            lastName: '鈴木',
        ));

        $this->assertSame('鈴木', $output->lastName);
        $this->assertSame('太郎', $output->firstName);
        $this->assertSame('鈴木 太郎', $output->fullName);
    }

    public function test_変更がない場合は更新日時が変わらない(): void
    {
        $expected = (new DateTimeImmutable(self::CREATED_AT))->format(DateTimeInterface::ATOM);

        $output = $this->useCase->execute(new UpdateEmployeeInput(
            id: $this->target->id()->value(),
            employeeNumber: 'emp-0001',
            lastName: '山田',
            firstName: '太郎',
            email: 'TARO@example.com',
            department: '開発部',
            hireDate: '2024-04-01',
            status: 'active',
        ));

        $this->assertSame($expected, $output->updatedAt);
    }

    public function test_存在しない社員は例外になる(): void
    {
        $this->expectException(EmployeeNotFoundException::class);

        $this->useCase->execute(new UpdateEmployeeInput(
            id: EmployeeId::generate()->value(),
            department: '営業部',
        ));
    }

    public function test_不正な形式の_i_dは例外になる(): void
    {
        $this->expectException(InvalidValueException::class);

        $this->useCase->execute(new UpdateEmployeeInput(id: 'not-a-uuid', department: '営業部'));
    }

    public function test_他の社員と同じ社員番号は例外になる(): void
    {
        $this->employees->save($this->employee('EMP-0002', 'hanako@example.com'));

        $this->expectException(DuplicateEmployeeNumberException::class);

        $this->useCase->execute(new UpdateEmployeeInput(
            id: $this->target->id()->value(),
            employeeNumber: 'EMP-0002',
        ));
    }

    public function test_他の社員と同じメールアドレスは例外になる(): void
    {
        $this->employees->save($this->employee('EMP-0002', 'hanako@example.com'));

        $this->expectException(DuplicateEmployeeEmailException::class);

        $this->useCase->execute(new UpdateEmployeeInput(
            id: $this->target->id()->value(),
            email: 'hanako@example.com',
        ));
    }

    public function test_自分自身と同じ社員番号なら更新できる(): void
    {
        $output = $this->useCase->execute(new UpdateEmployeeInput(
            id: $this->target->id()->value(),
            employeeNumber: 'EMP-0001',
            department: '営業部',
        ));

        $this->assertSame('EMP-0001', $output->employeeNumber);
        $this->assertSame('営業部', $output->department);
    }

    public function test_退職済みの社員は属性を変更できない(): void
    {
        $retired = $this->employee('EMP-0003', 'retired@example.com', EmploymentStatus::Retired);
        $this->employees->save($retired);

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('退職済みの社員は変更できません。');

        $this->useCase->execute(new UpdateEmployeeInput(
            id: $retired->id()->value(),
            department: '営業部',
        ));
    }

    public function test_復職と同時に他の項目も更新できる(): void
    {
        $retired = $this->employee('EMP-0003', 'retired@example.com', EmploymentStatus::Retired);
        $this->employees->save($retired);

        $output = $this->useCase->execute(new UpdateEmployeeInput(
            id: $retired->id()->value(),
            department: '営業部',
            status: 'active',
        ));

        $this->assertSame('active', $output->status);
        $this->assertSame('営業部', $output->department);
    }

    private function employee(
        string $employeeNumber,
        string $email,
        EmploymentStatus $status = EmploymentStatus::Active,
    ): Employee {
        return Employee::create(
            EmployeeId::generate(),
            EmployeeNumber::fromString($employeeNumber),
            new PersonName('山田', '太郎'),
            EmailAddress::fromString($email),
            Department::fromString('開発部'),
            HireDate::fromString('2024-04-01'),
            $status,
            new DateTimeImmutable(self::CREATED_AT),
        );
    }

    private function transactionManager(): TransactionManagerInterface
    {
        return new class implements TransactionManagerInterface
        {
            public function run(callable $operation): mixed
            {
                return $operation();
            }
        };
    }
}
