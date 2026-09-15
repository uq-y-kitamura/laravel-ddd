<?php

declare(strict_types=1);

namespace Domain\User\Exception;

use Domain\Shared\Exception\EntityNotFoundException;
use Domain\User\ValueObject\UserId;

/**
 * 指定されたユーザーが存在しない場合の例外。HTTP 404 に対応する。
 */
final class UserNotFoundException extends EntityNotFoundException
{
    public static function fromId(UserId $id): self
    {
        return new self(sprintf('指定されたユーザーが見つかりません。: %s', $id->value()));
    }
}
