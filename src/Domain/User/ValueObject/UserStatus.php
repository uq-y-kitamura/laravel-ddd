<?php

declare(strict_types=1);

namespace Domain\User\ValueObject;

use Domain\Shared\Exception\InvalidValueException;

/**
 * ユーザーの状態。
 */
enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    /**
     * @return list<string> 指定可能な値の一覧
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    public static function fromString(string $value): self
    {
        $status = self::tryFrom(trim($value));

        if ($status === null) {
            throw new InvalidValueException(
                sprintf('状態の値が不正です。: %s', $value)
            );
        }

        return $status;
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => '有効',
            self::Suspended => '停止中',
        };
    }
}
