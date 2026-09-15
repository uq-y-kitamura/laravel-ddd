<?php

declare(strict_types=1);

namespace Domain\Employee\ValueObject;

use Domain\Shared\Exception\InvalidValueException;
use Stringable;

/**
 * 氏名（姓・名）を表す値オブジェクト。
 */
final class PersonName implements Stringable
{
    private const MAX_LENGTH = 50;

    private readonly string $lastName;

    private readonly string $firstName;

    public function __construct(string $lastName, string $firstName)
    {
        $this->lastName = self::normalize($lastName, '姓');
        $this->firstName = self::normalize($firstName, '名');
    }

    public static function of(string $lastName, string $firstName): self
    {
        return new self($lastName, $firstName);
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function fullName(): string
    {
        return sprintf('%s %s', $this->lastName, $this->firstName);
    }

    public function equals(self $other): bool
    {
        return $this->lastName === $other->lastName && $this->firstName === $other->firstName;
    }

    public function __toString(): string
    {
        return $this->fullName();
    }

    private static function normalize(string $value, string $label): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new InvalidValueException(sprintf('%sは必須です。', $label));
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new InvalidValueException(
                sprintf('%sは %d 文字以内で入力してください。', $label, self::MAX_LENGTH)
            );
        }

        return $normalized;
    }
}
