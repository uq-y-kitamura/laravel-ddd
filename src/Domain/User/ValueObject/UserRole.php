<?php

declare(strict_types=1);

namespace Domain\User\ValueObject;

use Domain\Shared\Exception\InvalidValueException;

/**
 * ユーザーの権限。
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Member = 'member';

    /**
     * @return list<string> 指定可能な値の一覧
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    public static function fromString(string $value): self
    {
        $role = self::tryFrom(trim($value));

        if ($role === null) {
            throw new InvalidValueException(
                sprintf('権限の値が不正です。: %s', $value)
            );
        }

        return $role;
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => '管理者',
            self::Manager => 'マネージャー',
            self::Member => '一般',
        };
    }
}
