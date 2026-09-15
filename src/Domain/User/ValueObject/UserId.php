<?php

declare(strict_types=1);

namespace Domain\User\ValueObject;

use Domain\Shared\ValueObject\Uuid;

/**
 * ユーザーの識別子。
 */
final class UserId extends Uuid
{
    protected static function label(): string
    {
        return 'ユーザーID';
    }
}
