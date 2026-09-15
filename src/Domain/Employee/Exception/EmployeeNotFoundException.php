<?php

declare(strict_types=1);

namespace Domain\Employee\Exception;

use Domain\Employee\ValueObject\EmployeeId;
use Domain\Shared\Exception\EntityNotFoundException;

/**
 * 指定された社員が存在しない場合の例外。
 */
final class EmployeeNotFoundException extends EntityNotFoundException
{
    public static function fromId(EmployeeId $id): self
    {
        return new self(sprintf('社員が見つかりません。: %s', $id->value()));
    }
}
