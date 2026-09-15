<?php

declare(strict_types=1);

namespace Domain\User\Exception;

use Domain\Shared\Exception\BusinessRuleViolationException;

/**
 * 最後の管理者を削除・降格・停止しようとした場合の例外。
 */
final class LastAdminCannotBeRemovedException extends BusinessRuleViolationException
{
    public const MESSAGE = '管理者が 0 人になる操作は実行できません。';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct($message);
    }

    public static function occurred(): self
    {
        return new self;
    }
}
