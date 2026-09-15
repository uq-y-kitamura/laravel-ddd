<?php

declare(strict_types=1);

namespace Domain\User\Criteria;

use Domain\Shared\Criteria\Pagination;
use Domain\Shared\Exception\InvalidValueException;
use Domain\User\ValueObject\UserRole;
use Domain\User\ValueObject\UserStatus;

/**
 * ユーザー検索の条件。
 */
final class UserSearchCriteria
{
    public const SORT_BY_NAME = 'name';

    public const SORT_BY_EMAIL = 'email';

    public const SORT_BY_CREATED_AT = 'created_at';

    public const DEFAULT_SORT_BY = self::SORT_BY_CREATED_AT;

    public const SORT_DIRECTION_ASC = 'asc';

    public const SORT_DIRECTION_DESC = 'desc';

    public const DEFAULT_SORT_DIRECTION = self::SORT_DIRECTION_DESC;

    /**
     * @var list<string>
     */
    public const ALLOWED_SORT_BY = [
        self::SORT_BY_NAME,
        self::SORT_BY_EMAIL,
        self::SORT_BY_CREATED_AT,
    ];

    /**
     * @var list<string>
     */
    public const ALLOWED_SORT_DIRECTION = [
        self::SORT_DIRECTION_ASC,
        self::SORT_DIRECTION_DESC,
    ];

    private const MAX_KEYWORD_LENGTH = 100;

    /**
     * ユーザー名・メールアドレスへの部分一致キーワード。
     */
    public readonly ?string $keyword;

    public readonly ?UserRole $role;

    public readonly ?UserStatus $status;

    public readonly Pagination $pagination;

    public readonly string $sortBy;

    public readonly string $sortDirection;

    public function __construct(
        ?string $keyword = null,
        ?UserRole $role = null,
        ?UserStatus $status = null,
        ?Pagination $pagination = null,
        string $sortBy = self::DEFAULT_SORT_BY,
        string $sortDirection = self::DEFAULT_SORT_DIRECTION,
    ) {
        $this->keyword = self::normalizeKeyword($keyword);
        $this->role = $role;
        $this->status = $status;
        $this->pagination = $pagination ?? Pagination::of();
        $this->sortBy = self::normalizeSortBy($sortBy);
        $this->sortDirection = self::normalizeSortDirection($sortDirection);
    }

    public function hasKeyword(): bool
    {
        return $this->keyword !== null;
    }

    private static function normalizeKeyword(?string $keyword): ?string
    {
        if ($keyword === null) {
            return null;
        }

        $normalized = trim($keyword);

        if ($normalized === '') {
            return null;
        }

        if (mb_strlen($normalized) > self::MAX_KEYWORD_LENGTH) {
            throw new InvalidValueException(
                sprintf('検索キーワードは %d 文字以内で入力してください。', self::MAX_KEYWORD_LENGTH)
            );
        }

        return $normalized;
    }

    private static function normalizeSortBy(string $sortBy): string
    {
        $normalized = strtolower(trim($sortBy));

        if (! in_array($normalized, self::ALLOWED_SORT_BY, true)) {
            throw new InvalidValueException(
                sprintf('並び替え項目は %s のいずれかで指定してください。: %s', implode(' / ', self::ALLOWED_SORT_BY), $sortBy)
            );
        }

        return $normalized;
    }

    private static function normalizeSortDirection(string $sortDirection): string
    {
        $normalized = strtolower(trim($sortDirection));

        if (! in_array($normalized, self::ALLOWED_SORT_DIRECTION, true)) {
            throw new InvalidValueException(
                sprintf('並び順は %s のいずれかで指定してください。: %s', implode(' / ', self::ALLOWED_SORT_DIRECTION), $sortDirection)
            );
        }

        return $normalized;
    }
}
