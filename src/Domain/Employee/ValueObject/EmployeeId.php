<?php

declare(strict_types=1);

namespace Domain\Employee\ValueObject;

use Domain\Shared\ValueObject\Uuid;

/**
 * 社員を一意に識別する ID。
 */
final class EmployeeId extends Uuid
{
    protected static function label(): string
    {
        return '社員ID';
    }
}
