<?php

declare(strict_types=1);

namespace Domain\Employee\Exception;

use Domain\Employee\ValueObject\EmployeeNumber;
use Domain\Shared\Exception\DuplicateEntityException;

/**
 * 社員番号が既に使用されている場合の例外。
 */
final class DuplicateEmployeeNumberException extends DuplicateEntityException
{
    public static function fromNumber(EmployeeNumber $number): self
    {
        return new self(sprintf('社員番号は既に使用されています。: %s', $number->value()));
    }
}
