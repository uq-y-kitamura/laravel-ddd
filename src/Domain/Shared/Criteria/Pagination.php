<?php

declare(strict_types=1);

namespace Domain\Shared\Criteria;

use Domain\Shared\Exception\InvalidValueException;

/**
 * ページネーション条件を表す値オブジェクト。
 */
final class Pagination
{
    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 100;

    private function __construct(
        public readonly int $page,
        public readonly int $perPage,
    ) {}

    public static function of(int $page = 1, int $perPage = self::DEFAULT_PER_PAGE): self
    {
        if ($page < 1) {
            throw new InvalidValueException('ページ番号は 1 以上で指定してください。');
        }

        if ($perPage < 1 || $perPage > self::MAX_PER_PAGE) {
            throw new InvalidValueException(
                sprintf('1 ページあたりの件数は 1〜%d の範囲で指定してください。', self::MAX_PER_PAGE)
            );
        }

        return new self($page, $perPage);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function limit(): int
    {
        return $this->perPage;
    }
}
