<?php

declare(strict_types=1);

namespace Application\Employee\Dto;

use DateTimeInterface;
use Domain\Employee\Entity\Employee;

/**
 * 社員 1 件の出力 DTO。日時は ISO8601（ATOM）文字列で保持する。
 */
final readonly class EmployeeOutput
{
    public function __construct(
        public string $id,
        public string $employeeNumber,
        public string $lastName,
        public string $firstName,
        public string $fullName,
        public string $email,
        public string $department,
        public string $hireDate,
        public string $status,
        public string $statusLabel,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(Employee $employee): self
    {
        return new self(
            id: $employee->id()->value(),
            employeeNumber: $employee->employeeNumber()->value(),
            lastName: $employee->name()->lastName(),
            firstName: $employee->name()->firstName(),
            fullName: $employee->name()->fullName(),
            email: $employee->email()->value(),
            department: $employee->department()->value(),
            hireDate: $employee->hireDate()->toString(),
            status: $employee->status()->value,
            statusLabel: $employee->status()->label(),
            createdAt: $employee->createdAt()->format(DateTimeInterface::ATOM),
            updatedAt: $employee->updatedAt()->format(DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employeeNumber,
            'name' => [
                'last_name' => $this->lastName,
                'first_name' => $this->firstName,
                'full_name' => $this->fullName,
            ],
            'email' => $this->email,
            'department' => $this->department,
            'hire_date' => $this->hireDate,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
