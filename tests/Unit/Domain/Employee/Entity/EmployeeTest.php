<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Employee\Entity;

use DateTimeImmutable;
use Domain\Employee\Entity\Employee;
use Domain\Employee\ValueObject\Department;
use Domain\Employee\ValueObject\EmployeeId;
use Domain\Employee\ValueObject\EmployeeNumber;
use Domain\Employee\ValueObject\EmploymentStatus;
use Domain\Employee\ValueObject\HireDate;
use Domain\Employee\ValueObject\PersonName;
use Domain\Shared\Exception\InvalidValueException;
use Domain\Shared\ValueObject\EmailAddress;
use PHPUnit\Framework\TestCase;

final class EmployeeTest extends TestCase
{
    private const CREATED_AT = '2024-04-01 09:00:00';

    private const UPDATED_AT = '2024-05-01 10:00:00';

    public function test_createで生成すると作成日時と更新日時が同じになる(): void
    {
        $now = new DateTimeImmutable(self::CREATED_AT);
        $employee = $this->createEmployee(now: $now);

        $this->assertSame('EMP-0001', $employee->employeeNumber()->value());
        $this->assertSame('山田 太郎', $employee->name()->fullName());
        $this->assertSame('taro@example.com', $employee->email()->value());
        $this->assertSame('開発部', $employee->department()->value());
        $this->assertSame('2024-04-01', $employee->hireDate()->toString());
        $this->assertSame(EmploymentStatus::Active, $employee->status());
        $this->assertEquals($now, $employee->createdAt());
        $this->assertEquals($now, $employee->updatedAt());
    }

    public function test_reconstructは永続化された日時をそのまま復元する(): void
    {
        $createdAt = new DateTimeImmutable(self::CREATED_AT);
        $updatedAt = new DateTimeImmutable(self::UPDATED_AT);

        $employee = Employee::reconstruct(
            EmployeeId::generate(),
            EmployeeNumber::fromString('EMP-0001'),
            new PersonName('山田', '太郎'),
            EmailAddress::fromString('taro@example.com'),
            Department::fromString('開発部'),
            HireDate::fromString('2024-04-01'),
            EmploymentStatus::OnLeave,
            $createdAt,
            $updatedAt,
        );

        $this->assertEquals($createdAt, $employee->createdAt());
        $this->assertEquals($updatedAt, $employee->updatedAt());
        $this->assertSame(EmploymentStatus::OnLeave, $employee->status());
    }

    public function test_値が変わったときだけ更新日時が更新される(): void
    {
        $createdAt = new DateTimeImmutable(self::CREATED_AT);
        $employee = $this->createEmployee(now: $createdAt);
        $later = new DateTimeImmutable(self::UPDATED_AT);

        $employee->changeName(new PersonName('山田', '太郎'), $later);
        $this->assertEquals($createdAt, $employee->updatedAt(), '同じ値なら更新日時は変わらない');

        $employee->changeName(new PersonName('鈴木', '花子'), $later);
        $this->assertSame('鈴木 花子', $employee->name()->fullName());
        $this->assertEquals($later, $employee->updatedAt());
    }

    public function test_各属性を変更できる(): void
    {
        $employee = $this->createEmployee();
        $later = new DateTimeImmutable(self::UPDATED_AT);

        $employee->changeEmployeeNumber(EmployeeNumber::fromString('EMP-9999'), $later);
        $employee->changeEmail(EmailAddress::fromString('hanako@example.com'), $later);
        $employee->changeDepartment(Department::fromString('営業部'), $later);
        $employee->changeHireDate(HireDate::fromString('2025-10-01'), $later);
        $employee->changeStatus(EmploymentStatus::OnLeave, $later);

        $this->assertSame('EMP-9999', $employee->employeeNumber()->value());
        $this->assertSame('hanako@example.com', $employee->email()->value());
        $this->assertSame('営業部', $employee->department()->value());
        $this->assertSame('2025-10-01', $employee->hireDate()->toString());
        $this->assertSame(EmploymentStatus::OnLeave, $employee->status());
        $this->assertEquals($later, $employee->updatedAt());
    }

    public function test_退職済みの社員は氏名を変更できない(): void
    {
        $employee = $this->createEmployee(status: EmploymentStatus::Retired);

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('退職済みの社員は変更できません。');

        $employee->changeName(new PersonName('鈴木', '花子'), new DateTimeImmutable(self::UPDATED_AT));
    }

    public function test_退職済みの社員は所属部署を変更できない(): void
    {
        $employee = $this->createEmployee(status: EmploymentStatus::Retired);

        $this->expectException(InvalidValueException::class);

        $employee->changeDepartment(Department::fromString('営業部'), new DateTimeImmutable(self::UPDATED_AT));
    }

    public function test_退職済みの社員は在籍中へ復職できる(): void
    {
        $createdAt = new DateTimeImmutable(self::CREATED_AT);
        $employee = $this->createEmployee(status: EmploymentStatus::Retired, now: $createdAt);
        $later = new DateTimeImmutable(self::UPDATED_AT);

        $employee->changeStatus(EmploymentStatus::Active, $later);

        $this->assertSame(EmploymentStatus::Active, $employee->status());
        $this->assertEquals($later, $employee->updatedAt());

        // 復職後は他の属性も変更できる。
        $employee->changeDepartment(Department::fromString('営業部'), $later);
        $this->assertSame('営業部', $employee->department()->value());
    }

    public function test_退職済みから休職中へは遷移できない(): void
    {
        $employee = $this->createEmployee(status: EmploymentStatus::Retired);

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('退職済みの社員は変更できません。');

        $employee->changeStatus(EmploymentStatus::OnLeave, new DateTimeImmutable(self::UPDATED_AT));
    }

    public function test_退職済みへ同じ状態を再設定しても例外にならない(): void
    {
        $createdAt = new DateTimeImmutable(self::CREATED_AT);
        $employee = $this->createEmployee(status: EmploymentStatus::Retired, now: $createdAt);

        $employee->changeStatus(EmploymentStatus::Retired, new DateTimeImmutable(self::UPDATED_AT));

        $this->assertSame(EmploymentStatus::Retired, $employee->status());
        $this->assertEquals($createdAt, $employee->updatedAt());
    }

    public function test_在籍中から退職済みへ遷移できる(): void
    {
        $employee = $this->createEmployee();
        $later = new DateTimeImmutable(self::UPDATED_AT);

        $employee->changeStatus(EmploymentStatus::Retired, $later);

        $this->assertTrue($employee->isRetired());
        $this->assertEquals($later, $employee->updatedAt());
    }

    private function createEmployee(
        ?EmploymentStatus $status = null,
        ?DateTimeImmutable $now = null,
    ): Employee {
        return Employee::create(
            EmployeeId::generate(),
            EmployeeNumber::fromString('EMP-0001'),
            new PersonName('山田', '太郎'),
            EmailAddress::fromString('taro@example.com'),
            Department::fromString('開発部'),
            HireDate::fromString('2024-04-01'),
            $status ?? EmploymentStatus::Active,
            $now ?? new DateTimeImmutable(self::CREATED_AT),
        );
    }
}
