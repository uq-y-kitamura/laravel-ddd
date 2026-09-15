<?php

declare(strict_types=1);

namespace Application\Employee\UseCase;

/**
 * 社員一覧取得の入力 DTO。null は「指定なし（既定値を使用）」を意味する。
 */
final readonly class ListEmployeesInput
{
    public function __construct(
        public ?int $page = null,
        public ?int $perPage = null,
        public ?string $keyword = null,
        public ?string $department = null,
        public ?string $status = null,
        public ?string $sortBy = null,
        public ?string $sortDirection = null,
    ) {}
}
