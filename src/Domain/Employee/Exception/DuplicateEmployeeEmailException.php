<?php

declare(strict_types=1);

namespace Domain\Employee\Exception;

use Domain\Shared\Exception\DuplicateEntityException;
use Domain\Shared\ValueObject\EmailAddress;

/**
 * メールアドレスが既に使用されている場合の例外。
 */
final class DuplicateEmployeeEmailException extends DuplicateEntityException
{
    public static function fromEmail(EmailAddress $email): self
    {
        return new self(sprintf('メールアドレスは既に使用されています。: %s', $email->value()));
    }
}
