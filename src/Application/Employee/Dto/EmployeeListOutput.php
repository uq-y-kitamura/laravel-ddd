<?php

declare(strict_types=1);

namespace Application\Employee\Dto;

use Domain\Employee\Entity\Employee;
use Domain\Shared\Criteria\PaginatedResult;

/**
 * 社員一覧の出力 DTO。
 */
final readonly class EmployeeListOutput
{
    /**
     * @param  list<EmployeeOutput>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
        public int $totalPages,
    ) {}

    /**
     * @param  PaginatedResult<Employee>  $result
     */
    public static function fromPaginatedResult(PaginatedResult $result): self
    {
        return new self(
            items: array_values(array_map(
                static fn (Employee $employee): EmployeeOutput => EmployeeOutput::fromEntity($employee),
                $result->items
            )),
            total: $result->total,
            page: $result->pagination->page,
            perPage: $result->pagination->perPage,
            totalPages: $result->totalPages(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(
                static fn (EmployeeOutput $item): array => $item->toArray(),
                $this->items
            ),
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total_pages' => $this->totalPages,
        ];
    }
}
