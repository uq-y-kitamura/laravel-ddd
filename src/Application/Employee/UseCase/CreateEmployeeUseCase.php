<?php

declare(strict_types=1);

namespace Application\Employee\UseCase;

use Application\Employee\Dto\EmployeeOutput;
use Application\Shared\Transaction\TransactionManagerInterface;
use DateTimeImmutable;
use Domain\Employee\Entity\Employee;
use Domain\Employee\Exception\DuplicateEmployeeEmailException;
use Domain\Employee\Exception\DuplicateEmployeeNumberException;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Domain\Employee\ValueObject\Department;
use Domain\Employee\ValueObject\EmployeeNumber;
use Domain\Employee\ValueObject\EmploymentStatus;
use Domain\Employee\ValueObject\HireDate;
use Domain\Employee\ValueObject\PersonName;
use Domain\Shared\ValueObject\EmailAddress;

/**
 * 社員を新規登録するユースケース。
 */
final readonly class CreateEmployeeUseCase
{
    public function __construct(
        private EmployeeRepositoryInterface $employees,
        private TransactionManagerInterface $transaction,
    ) {}

    public function execute(CreateEmployeeInput $input): EmployeeOutput
    {
        $employeeNumber = EmployeeNumber::fromString($input->employeeNumber);
        $name = new PersonName($input->lastName, $input->firstName);
        $email = EmailAddress::fromString($input->email);
        $department = Department::fromString($input->department);
        $hireDate = HireDate::fromString($input->hireDate);
        $status = $input->status === null
            ? EmploymentStatus::Active
            : EmploymentStatus::fromString($input->status);

        return $this->transaction->run(function () use (
            $employeeNumber,
            $name,
            $email,
            $department,
            $hireDate,
            $status,
        ): EmployeeOutput {
            if ($this->employees->existsByEmployeeNumber($employeeNumber)) {
                throw DuplicateEmployeeNumberException::fromNumber($employeeNumber);
            }

            if ($this->employees->existsByEmail($email)) {
                throw DuplicateEmployeeEmailException::fromEmail($email);
            }

            $employee = Employee::create(
                $this->employees->nextIdentity(),
                $employeeNumber,
                $name,
                $email,
                $department,
                $hireDate,
                $status,
                $this->currentTime(),
            );

            $this->employees->save($employee);

            return EmployeeOutput::fromEntity($employee);
        });
    }

    private function currentTime(): DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
}
