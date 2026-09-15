<?php

declare(strict_types=1);

namespace Domain\User\ValueObject;

use Domain\Shared\Exception\InvalidValueException;
use Stringable;

/**
 * ユーザーの表示名を表す値オブジェクト。
 */
final class UserName implements Stringable
{
    public const MAX_LENGTH = 100;

    private readonly string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new InvalidValueException('ユーザー名は必須です。');
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new InvalidValueException(
                sprintf('ユーザー名は %d 文字以内で入力してください。', self::MAX_LENGTH)
            );
        }

        $this->value = $normalized;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
