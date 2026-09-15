<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Employee;

use Application\Employee\UseCase\CreateEmployeeInput;
use Application\Employee\UseCase\CreateEmployeeUseCase;
use Application\Shared\Transaction\TransactionManagerInterface;
use DateTimeImmutable;
use Domain\Employee\Entity\Employee;
use Domain\Employee\Exception\DuplicateEmployeeEmailException;
use Domain\Employee\Exception\DuplicateEmployeeNumberException;
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

final class CreateEmployeeUseCaseTest extends TestCase
{
    private InMemoryEmployeeRepository $employees;

    private CreateEmployeeUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employees = new InMemoryEmployeeRepository;
        $this->useCase = new CreateEmployeeUseCase($this->employees, $this->transactionManager());
    }

    public function test_社員を登録できる(): void
    {
        $output = $this->useCase->execute(new CreateEmployeeInput(
            employeeNumber: 'emp-0001',
            lastName: '  山田  ',
            firstName: '太郎',
            email: 'TARO@Example.com',
            department: '開発部',
            hireDate: '2024-04-01',
            status: 'on_leave',
        ));

        $this->assertSame('EMP-0001', $output->employeeNumber, '社員番号は大文字に正規化される');
        $this->assertSame('山田', $output->lastName);
        $this->assertSame('山田 太郎', $output->fullName);
        $this->assertSame('taro@example.com', $output->email, 'メールアドレスは小文字に正規化される');
        $this->assertSame('開発部', $output->department);
        $this->assertSame('2024-04-01', $output->hireDate);
        $this->assertSame('on_leave', $output->status);
        $this->assertSame('休職中', $output->statusLabel);
        $this->assertSame($output->createdAt, $output->updatedAt);
        $this->assertSame(1, $this->employees->count());

        $saved = $this->employees->findById(EmployeeId::fromString($output->id));
        $this->assertNotNull($saved);
        $this->assertSame('EMP-0001', $saved->employeeNumber()->value());
    }

    public function test_在籍状況を省略すると在籍中になる(): void
    {
        $output = $this->useCase->execute($this->input());

        $this->assertSame('active', $output->status);
        $this->assertSame('在籍中', $output->statusLabel);
    }

    public function test_社員番号が重複すると例外になる(): void
    {
        $this->employees->save($this->existingEmployee('EMP-0001', 'other@example.com'));

        $this->expectException(DuplicateEmployeeNumberException::class);

        $this->useCase->execute($this->input(employeeNumber: 'EMP-0001'));
    }

    public function test_メールアドレスが重複すると例外になる(): void
    {
        $this->employees->save($this->existingEmployee('EMP-9999', 'taro@example.com'));

        $this->expectException(DuplicateEmployeeEmailException::class);

        $this->useCase->execute($this->input(email: 'TARO@example.com'));
    }

    public function test_不正な入社日は例外になり保存されない(): void
    {
        try {
            $this->useCase->execute($this->input(hireDate: '2024-02-30'));
            $this->fail('InvalidValueException が送出されるべきです。');
        } catch (InvalidValueException) {
            $this->assertSame(0, $this->employees->count());
        }
    }

    private function input(
        string $employeeNumber = 'EMP-0001',
        string $email = 'taro@example.com',
        string $hireDate = '2024-04-01',
    ): CreateEmployeeInput {
        return new CreateEmployeeInput(
            employeeNumber: $employeeNumber,
            lastName: '山田',
            firstName: '太郎',
            email: $email,
            department: '開発部',
            hireDate: $hireDate,
        );
    }

    private function existingEmployee(string $employeeNumber, string $email): Employee
    {
        return Employee::create(
            EmployeeId::generate(),
            EmployeeNumber::fromString($employeeNumber),
            new PersonName('既存', '社員'),
            EmailAddress::fromString($email),
            Department::fromString('総務部'),
            HireDate::fromString('2020-04-01'),
            EmploymentStatus::Active,
            new DateTimeImmutable('2020-04-01 09:00:00'),
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
