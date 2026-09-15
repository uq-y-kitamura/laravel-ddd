<?php

declare(strict_types=1);

namespace Application\Employee\UseCase;

use Application\Shared\Transaction\TransactionManagerInterface;
use Domain\Employee\Exception\EmployeeNotFoundException;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Domain\Employee\ValueObject\EmployeeId;

/**
 * 社員を削除するユースケース。
 */
final readonly class DeleteEmployeeUseCase
{
    public function __construct(
        private EmployeeRepositoryInterface $employees,
        private TransactionManagerInterface $transaction,
    ) {}

    public function execute(string $employeeId): void
    {
        $id = EmployeeId::fromString($employeeId);

        $this->transaction->run(function () use ($id): void {
            if ($this->employees->findById($id) === null) {
                throw EmployeeNotFoundException::fromId($id);
            }

            $this->employees->delete($id);
        });
    }
}
