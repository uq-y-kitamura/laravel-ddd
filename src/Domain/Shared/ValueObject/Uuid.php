<?php

declare(strict_types=1);

namespace Domain\Shared\ValueObject;

use Domain\Shared\Exception\InvalidValueException;
use Stringable;

/**
 * UUID v4 を表す識別子の基底値オブジェクト。
 * 各集約は本クラスを継承して専用の ID 型を定義する。
 */
abstract class Uuid implements Stringable
{
    private const PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    private readonly string $value;

    final public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if (preg_match(self::PATTERN, $normalized) !== 1) {
            throw new InvalidValueException(
                sprintf('%s は UUID v4 形式である必要があります。: %s', static::label(), $value)
            );
        }

        $this->value = $normalized;
    }

    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public static function generate(): static
    {
        return new static(self::randomUuidV4());
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return static::class === $other::class && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    protected static function label(): string
    {
        return '識別子';
    }

    private static function randomUuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
