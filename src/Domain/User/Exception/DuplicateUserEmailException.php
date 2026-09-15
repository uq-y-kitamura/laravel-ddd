<?php

declare(strict_types=1);

namespace Domain\User\Exception;

use Domain\Shared\Exception\DuplicateEntityException;
use Domain\Shared\ValueObject\EmailAddress;

/**
 * メールアドレスが既に使用されている場合の例外。HTTP 409 に対応する。
 */
final class DuplicateUserEmailException extends DuplicateEntityException
{
    public static function fromEmail(EmailAddress $email): self
    {
        return new self(sprintf('このメールアドレスは既に使用されています。: %s', $email->value()));
    }
}
