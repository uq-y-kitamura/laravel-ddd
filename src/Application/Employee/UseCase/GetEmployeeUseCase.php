<?php

declare(strict_types=1);

namespace Application\Employee\UseCase;

use Application\Employee\Dto\EmployeeOutput;
use Domain\Employee\Exception\EmployeeNotFoundException;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Domain\Employee\ValueObject\EmployeeId;

/**
 * 社員を 1 件取得するユースケース（読み取り専用）。
 */
final readonly class GetEmployeeUseCase
{
    public function __construct(private EmployeeRepositoryInterface $employees) {}

    public function execute(string $employeeId): EmployeeOutput
    {
        $id = EmployeeId::fromString($employeeId);
        $employee = $this->employees->findById($id);

        if ($employee === null) {
            throw EmployeeNotFoundException::fromId($id);
        }

        return EmployeeOutput::fromEntity($employee);
    }
}
