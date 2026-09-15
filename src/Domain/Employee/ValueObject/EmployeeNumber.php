<?php

declare(strict_types=1);

namespace Domain\Employee\ValueObject;

use Domain\Shared\Exception\InvalidValueException;
use Stringable;

/**
 * 社員番号を表す値オブジェクト。
 * 前後の空白を除去し大文字へ正規化したうえで、英数字とハイフンのみ 3〜20 文字を許可する。
 */
final class EmployeeNumber implements Stringable
{
    private const PATTERN = '/^[A-Z0-9-]{3,20}$/';

    private const MIN_LENGTH = 3;

    private const MAX_LENGTH = 20;

    private readonly string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtoupper(trim($value));

        if ($normalized === '') {
            throw new InvalidValueException('社員番号は必須です。');
        }

        if (preg_match(self::PATTERN, $normalized) !== 1) {
            throw new InvalidValueException(
                sprintf(
                    '社員番号は半角英数字とハイフンのみ %d〜%d 文字で入力してください。: %s',
                    self::MIN_LENGTH,
                    self::MAX_LENGTH,
                    $value
                )
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
