<?php

declare(strict_types=1);

namespace Domain\Employee\Entity;

use DateTimeImmutable;
use Domain\Employee\ValueObject\Department;
use Domain\Employee\ValueObject\EmployeeId;
use Domain\Employee\ValueObject\EmployeeNumber;
use Domain\Employee\ValueObject\EmploymentStatus;
use Domain\Employee\ValueObject\HireDate;
use Domain\Employee\ValueObject\PersonName;
use Domain\Shared\Exception\InvalidValueException;
use Domain\Shared\ValueObject\EmailAddress;

/**
 * 社員集約のルートエンティティ。
 *
 * 不変条件:
 *  - 退職済み（Retired）の社員は、在籍状況を「在籍中」へ戻す（復職）以外の変更を受け付けない。
 *  - 値が実際に変化したときだけ updatedAt を更新する。
 */
final class Employee
{
    private const RETIRED_IMMUTABLE_MESSAGE = '退職済みの社員は変更できません。';

    private function __construct(
        private readonly EmployeeId $id,
        private EmployeeNumber $employeeNumber,
        private PersonName $name,
        private EmailAddress $email,
        private Department $department,
        private HireDate $hireDate,
        private EmploymentStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    /**
     * 新規に社員を登録する。
     */
    public static function create(
        EmployeeId $id,
        EmployeeNumber $employeeNumber,
        PersonName $name,
        EmailAddress $email,
        Department $department,
        HireDate $hireDate,
        EmploymentStatus $status,
        DateTimeImmutable $now,
    ): self {
        return new self($id, $employeeNumber, $name, $email, $department, $hireDate, $status, $now, $now);
    }

    /**
     * 永続化されたデータから社員を復元する。
     */
    public static function reconstruct(
        EmployeeId $id,
        EmployeeNumber $employeeNumber,
        PersonName $name,
        EmailAddress $email,
        Department $department,
        HireDate $hireDate,
        EmploymentStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $employeeNumber, $name, $email, $department, $hireDate, $status, $createdAt, $updatedAt);
    }

    public function id(): EmployeeId
    {
        return $this->id;
    }

    public function employeeNumber(): EmployeeNumber
    {
        return $this->employeeNumber;
    }

    public function name(): PersonName
    {
        return $this->name;
    }

    public function email(): EmailAddress
    {
        return $this->email;
    }

    public function department(): Department
    {
        return $this->department;
    }

    public function hireDate(): HireDate
    {
        return $this->hireDate;
    }

    public function status(): EmploymentStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isRetired(): bool
    {
        return $this->status === EmploymentStatus::Retired;
    }

    public function changeEmployeeNumber(EmployeeNumber $employeeNumber, DateTimeImmutable $now): void
    {
        $this->ensureModifiable();

        if ($this->employeeNumber->equals($employeeNumber)) {
            return;
        }

        $this->employeeNumber = $employeeNumber;
        $this->touch($now);
    }

    public function changeName(PersonName $name, DateTimeImmutable $now): void
    {
        $this->ensureModifiable();

        if ($this->name->equals($name)) {
            return;
        }

        $this->name = $name;
        $this->touch($now);
    }

    public function changeEmail(EmailAddress $email, DateTimeImmutable $now): void
    {
        $this->ensureModifiable();

        if ($this->email->equals($email)) {
            return;
        }

        $this->email = $email;
        $this->touch($now);
    }

    public function changeDepartment(Department $department, DateTimeImmutable $now): void
    {
        $this->ensureModifiable();

        if ($this->department->equals($department)) {
            return;
        }

        $this->department = $department;
        $this->touch($now);
    }

    public function changeHireDate(HireDate $hireDate, DateTimeImmutable $now): void
    {
        $this->ensureModifiable();

        if ($this->hireDate->equals($hireDate)) {
            return;
        }

        $this->hireDate = $hireDate;
        $this->touch($now);
    }

    /**
     * 在籍状況を変更する。
     * 退職済みからの遷移は「在籍中」への復職のみ許可する。
     */
    public function changeStatus(EmploymentStatus $status, DateTimeImmutable $now): void
    {
        if ($this->status === $status) {
            return;
        }

        if ($this->isRetired() && $status !== EmploymentStatus::Active) {
            throw new InvalidValueException(self::RETIRED_IMMUTABLE_MESSAGE);
        }

        $this->status = $status;
        $this->touch($now);
    }

    /**
     * 退職済みの社員に対する属性変更を禁止する。
     */
    private function ensureModifiable(): void
    {
        if ($this->isRetired()) {
            throw new InvalidValueException(self::RETIRED_IMMUTABLE_MESSAGE);
        }
    }

    private function touch(DateTimeImmutable $now): void
    {
        $this->updatedAt = $now;
    }
}
