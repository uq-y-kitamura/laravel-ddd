<?php

declare(strict_types=1);

namespace Domain\Employee\ValueObject;

use Domain\Shared\Exception\InvalidValueException;

/**
 * 在籍状況。
 */
enum EmploymentStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Retired = 'retired';

    public static function fromString(string $value): self
    {
        $status = self::tryFrom(trim($value));

        if ($status === null) {
            throw new InvalidValueException(
                sprintf('在籍状況は %s のいずれかで指定してください。: %s', implode(' / ', self::values()), $value)
            );
        }

        return $status;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => '在籍中',
            self::OnLeave => '休職中',
            self::Retired => '退職',
        };
    }
}
