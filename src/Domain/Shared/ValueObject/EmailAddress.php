<?php

declare(strict_types=1);

namespace Domain\Shared\ValueObject;

use Domain\Shared\Exception\InvalidValueException;
use Stringable;

/**
 * メールアドレスを表す値オブジェクト（全ドメイン共有）。
 */
final class EmailAddress implements Stringable
{
    private const MAX_LENGTH = 255;

    private readonly string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if ($normalized === '') {
            throw new InvalidValueException('メールアドレスは必須です。');
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new InvalidValueException(
                sprintf('メールアドレスは %d 文字以内で入力してください。', self::MAX_LENGTH)
            );
        }

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidValueException(sprintf('メールアドレスの形式が不正です。: %s', $value));
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
