<?php

declare(strict_types=1);

namespace Application\Employee\UseCase;

use Application\Employee\Dto\EmployeeOutput;
use Application\Shared\Transaction\TransactionManagerInterface;
use DateTimeImmutable;
use Domain\Employee\Entity\Employee;
use Domain\Employee\Exception\DuplicateEmployeeEmailException;
use Domain\Employee\Exception\DuplicateEmployeeNumberException;
use Domain\Employee\Exception\EmployeeNotFoundException;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Domain\Employee\ValueObject\Department;
use Domain\Employee\ValueObject\EmployeeId;
use Domain\Employee\ValueObject\EmployeeNumber;
use Domain\Employee\ValueObject\EmploymentStatus;
use Domain\Employee\ValueObject\HireDate;
use Domain\Employee\ValueObject\PersonName;
use Domain\Shared\ValueObject\EmailAddress;

/**
 * 社員情報を部分更新するユースケース。
 */
final readonly class UpdateEmployeeUseCase
{
    public function __construct(
        private EmployeeRepositoryInterface $employees,
        private TransactionManagerInterface $transaction,
    ) {}

    public function execute(UpdateEmployeeInput $input): EmployeeOutput
    {
        $id = EmployeeId::fromString($input->id);

        return $this->transaction->run(function () use ($id, $input): EmployeeOutput {
            $employee = $this->employees->findById($id);

            if ($employee === null) {
                throw EmployeeNotFoundException::fromId($id);
            }

            $now = $this->currentTime();

            // 復職（退職済み → 在籍中）と他項目の変更を同一リクエストで行えるよう、
            // 在籍状況の変更を最初に適用する。
            if ($input->status !== null) {
                $employee->changeStatus(EmploymentStatus::fromString($input->status), $now);
            }

            if ($input->employeeNumber !== null) {
                $this->applyEmployeeNumber($employee, $input->employeeNumber, $now);
            }

            if ($input->hasNameChange()) {
                $employee->changeName(
                    new PersonName(
                        $input->lastName ?? $employee->name()->lastName(),
                        $input->firstName ?? $employee->name()->firstName(),
                    ),
                    $now,
                );
            }

            if ($input->email !== null) {
                $this->applyEmail($employee, $input->email, $now);
            }

            if ($input->department !== null) {
                $employee->changeDepartment(Department::fromString($input->department), $now);
            }

            if ($input->hireDate !== null) {
                $employee->changeHireDate(HireDate::fromString($input->hireDate), $now);
            }

            $this->employees->save($employee);

            return EmployeeOutput::fromEntity($employee);
        });
    }

    private function applyEmployeeNumber(Employee $employee, string $value, DateTimeImmutable $now): void
    {
        $employeeNumber = EmployeeNumber::fromString($value);

        // 値が変わらない場合は重複チェックを省略する（自分自身は常に除外される）。
        if (! $employee->employeeNumber()->equals($employeeNumber)
            && $this->employees->existsByEmployeeNumber($employeeNumber, $employee->id())) {
            throw DuplicateEmployeeNumberException::fromNumber($employeeNumber);
        }

        $employee->changeEmployeeNumber($employeeNumber, $now);
    }

    private function applyEmail(Employee $employee, string $value, DateTimeImmutable $now): void
    {
        $email = EmailAddress::fromString($value);

        // 値が変わらない場合は重複チェックを省略する（自分自身は常に除外される）。
        if (! $employee->email()->equals($email)
            && $this->employees->existsByEmail($email, $employee->id())) {
            throw DuplicateEmployeeEmailException::fromEmail($email);
        }

        $employee->changeEmail($email, $now);
    }

    private function currentTime(): DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
}
