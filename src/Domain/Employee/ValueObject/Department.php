<?php

declare(strict_types=1);

namespace Domain\Employee\ValueObject;

use Domain\Shared\Exception\InvalidValueException;
use Stringable;

/**
 * 所属部署を表す値オブジェクト。
 */
final class Department implements Stringable
{
    private const MAX_LENGTH = 100;

    private readonly string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new InvalidValueException('所属部署は必須です。');
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new InvalidValueException(
                sprintf('所属部署は %d 文字以内で入力してください。', self::MAX_LENGTH)
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
