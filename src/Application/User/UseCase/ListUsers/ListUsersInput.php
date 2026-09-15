<?php

declare(strict_types=1);

namespace Application\User\UseCase\ListUsers;

/**
 * ユーザー一覧取得の入力 DTO。null は「指定なし（既定値を使う）」を意味する。
 */
final readonly class ListUsersInput
{
    public function __construct(
        public ?int $page = null,
        public ?int $perPage = null,
        public ?string $keyword = null,
        public ?string $role = null,
        public ?string $status = null,
        public ?string $sortBy = null,
        public ?string $sortDirection = null,
    ) {}
}
