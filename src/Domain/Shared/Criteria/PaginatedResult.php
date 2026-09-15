<?php

declare(strict_types=1);

namespace Domain\Shared\Criteria;

/**
 * ページングされた検索結果。
 *
 * @template T
 */
final class PaginatedResult
{
    /**
     * @param  list<T>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly Pagination $pagination,
    ) {}

    public function totalPages(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        return (int) ceil($this->total / $this->pagination->perPage);
    }

    /**
     * @template U
     *
     * @param  callable(T): U  $mapper
     * @return self<U>
     */
    public function map(callable $mapper): self
    {
        return new self(array_values(array_map($mapper, $this->items)), $this->total, $this->pagination);
    }
}
