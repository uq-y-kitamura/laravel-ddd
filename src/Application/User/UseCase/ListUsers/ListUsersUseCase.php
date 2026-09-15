<?php

declare(strict_types=1);

namespace Application\User\UseCase\ListUsers;

use Application\User\Dto\UserListOutput;
use Domain\Shared\Criteria\Pagination;
use Domain\User\Criteria\UserSearchCriteria;
use Domain\User\Repository\UserRepositoryInterface;
use Domain\User\ValueObject\UserRole;
use Domain\User\ValueObject\UserStatus;

/**
 * ユーザーを検索するユースケース（読み取り専用）。
 */
final readonly class ListUsersUseCase
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function execute(ListUsersInput $input): UserListOutput
    {
        $criteria = new UserSearchCriteria(
            keyword: $input->keyword,
            role: $input->role === null ? null : UserRole::fromString($input->role),
            status: $input->status === null ? null : UserStatus::fromString($input->status),
            pagination: Pagination::of(
                $input->page ?? 1,
                $input->perPage ?? Pagination::DEFAULT_PER_PAGE,
            ),
            sortBy: $input->sortBy ?? UserSearchCriteria::DEFAULT_SORT_BY,
            sortDirection: $input->sortDirection ?? UserSearchCriteria::DEFAULT_SORT_DIRECTION,
        );

        return UserListOutput::fromPaginatedResult($this->users->search($criteria));
    }
}
