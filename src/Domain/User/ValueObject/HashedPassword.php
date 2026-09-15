<?php

declare(strict_types=1);

namespace Domain\User\ValueObject;

use Domain\Shared\Exception\InvalidValueException;
use SensitiveParameter;

/**
 * ハッシュ済みパスワードを表す値オブジェクト。
 */
final class HashedPassword
{
    private readonly string $value;

    public function __construct(#[SensitiveParameter] string $value)
    {
        if (trim($value) === '') {
            throw new InvalidValueException('ハッシュ済みパスワードは必須です。');
        }

        $this->value = $value;
    }

    public static function fromString(#[SensitiveParameter] string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }

    /**
     * var_dump() などでハッシュが漏れないようにマスクする。
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['value' => '********'];
    }
}
