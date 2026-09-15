<?php

declare(strict_types=1);

namespace Application\Employee\UseCase;

use Application\Employee\Dto\EmployeeListOutput;
use Domain\Employee\Criteria\EmployeeSearchCriteria;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Domain\Employee\ValueObject\Department;
use Domain\Employee\ValueObject\EmploymentStatus;
use Domain\Shared\Criteria\Pagination;

/**
 * 社員を検索して一覧を返すユースケース（読み取り専用）。
 */
final readonly class ListEmployeesUseCase
{
    public function __construct(private EmployeeRepositoryInterface $employees) {}

    public function execute(ListEmployeesInput $input): EmployeeListOutput
    {
        $criteria = new EmployeeSearchCriteria(
            keyword: $input->keyword,
            department: $input->department === null ? null : Department::fromString($input->department),
            status: $input->status === null ? null : EmploymentStatus::fromString($input->status),
            pagination: Pagination::of(
                $input->page ?? 1,
                $input->perPage ?? Pagination::DEFAULT_PER_PAGE,
            ),
            sortBy: $input->sortBy ?? EmployeeSearchCriteria::DEFAULT_SORT_BY,
            sortDirection: $input->sortDirection ?? EmployeeSearchCriteria::DEFAULT_SORT_DIRECTION,
        );

        return EmployeeListOutput::fromPaginatedResult($this->employees->search($criteria));
    }
}
