<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Eloquent\Mapper;

use DateTimeImmutable;
use DateTimeInterface;
use Domain\Employee\Entity\Employee;
use Domain\Employee\ValueObject\Department;
use Domain\Employee\ValueObject\EmployeeId;
use Domain\Employee\ValueObject\EmployeeNumber;
use Domain\Employee\ValueObject\EmploymentStatus;
use Domain\Employee\ValueObject\HireDate;
use Domain\Employee\ValueObject\PersonName;
use Domain\Shared\Exception\InvalidValueException;
use Domain\Shared\ValueObject\EmailAddress;
use Infrastructure\Persistence\Eloquent\Model\EmployeeRecord;

/**
 * Eloquent レコードとドメインエンティティの相互変換を担う。
 * Carbon などのフレームワーク固有の型はここで素の DateTimeImmutable に変換する。
 */
final class EmployeeMapper
{
    public function toEntity(EmployeeRecord $record): Employee
    {
        return Employee::reconstruct(
            EmployeeId::fromString((string) $record->getAttribute('id')),
            EmployeeNumber::fromString((string) $record->getAttribute('employee_number')),
            new PersonName(
                (string) $record->getAttribute('last_name'),
                (string) $record->getAttribute('first_name'),
            ),
            EmailAddress::fromString((string) $record->getAttribute('email')),
            Department::fromString((string) $record->getAttribute('department')),
            HireDate::fromDateTime($this->toDateTimeImmutable($record->getAttribute('hire_date'), 'hire_date')),
            EmploymentStatus::fromString((string) $record->getAttribute('status')),
            $this->toDateTimeImmutable($record->getAttribute('created_at'), 'created_at'),
            $this->toDateTimeImmutable($record->getAttribute('updated_at'), 'updated_at'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Employee $employee): array
    {
        return [
            'id' => $employee->id()->value(),
            'employee_number' => $employee->employeeNumber()->value(),
            'last_name' => $employee->name()->lastName(),
            'first_name' => $employee->name()->firstName(),
            'email' => $employee->email()->value(),
            'department' => $employee->department()->value(),
            'hire_date' => $employee->hireDate()->toString(),
            'status' => $employee->status()->value,
            'created_at' => $employee->createdAt(),
            'updated_at' => $employee->updatedAt(),
        ];
    }

    public function fillRecord(EmployeeRecord $record, Employee $employee): EmployeeRecord
    {
        $record->forceFill($this->toAttributes($employee));

        return $record;
    }

    private function toDateTimeImmutable(mixed $value, string $column): DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (is_string($value) && $value !== '') {
            return new DateTimeImmutable($value);
        }

        throw new InvalidValueException(
            sprintf('社員レコードの %s を日時として解釈できません。', $column)
        );
    }
}
