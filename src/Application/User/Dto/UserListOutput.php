<?php

declare(strict_types=1);

namespace Application\User\Dto;

use Domain\Shared\Criteria\PaginatedResult;
use Domain\User\Entity\User;

/**
 * ユーザー一覧の出力 DTO。
 */
final readonly class UserListOutput
{
    /**
     * @param  list<UserOutput>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
        public int $totalPages,
    ) {}

    /**
     * @param  PaginatedResult<User>  $result
     */
    public static function fromPaginatedResult(PaginatedResult $result): self
    {
        return new self(
            items: array_values(array_map(
                static fn (User $user): UserOutput => UserOutput::fromEntity($user),
                $result->items
            )),
            total: $result->total,
            page: $result->pagination->page,
            perPage: $result->pagination->perPage,
            totalPages: $result->totalPages(),
        );
    }

    /**
     * @return array{
     *     items: list<array<string, string>>,
     *     total: int,
     *     page: int,
     *     per_page: int,
     *     total_pages: int
     * }
     */
    public function toArray(): array
    {
        return [
            'items' => array_values(array_map(
                static fn (UserOutput $item): array => $item->toArray(),
                $this->items
            )),
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total_pages' => $this->totalPages,
        ];
    }
}
