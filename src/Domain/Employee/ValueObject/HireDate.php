<?php

declare(strict_types=1);

namespace Domain\Employee\ValueObject;

use DateTimeImmutable;
use DateTimeInterface;
use Domain\Shared\Exception\InvalidValueException;
use Stringable;

/**
 * 入社日を表す値オブジェクト。
 * 時刻成分を持たない日付として扱い、未来日も許容する。
 */
final class HireDate implements Stringable
{
    public const FORMAT = 'Y-m-d';

    private const STRICT_PATTERN = '/^\d{4}-\d{2}-\d{2}$/';

    private readonly DateTimeImmutable $value;

    public function __construct(DateTimeImmutable $value)
    {
        // 日付単位の値として扱うため時刻成分を切り捨てる。
        $this->value = $value->setTime(0, 0, 0);
    }

    /**
     * Y-m-d 形式の文字列から生成する（厳密パース）。
     */
    public static function fromString(string $ymd): self
    {
        $normalized = trim($ymd);

        if (preg_match(self::STRICT_PATTERN, $normalized) !== 1) {
            throw new InvalidValueException(
                sprintf('入社日は %s 形式で入力してください。: %s', self::FORMAT, $ymd)
            );
        }

        // 先頭の "!" で未指定フィールドを初期化し、時刻成分の混入を防ぐ。
        $parsed = DateTimeImmutable::createFromFormat('!'.self::FORMAT, $normalized);
        $errors = DateTimeImmutable::getLastErrors();

        if ($parsed === false || ($errors !== false && ($errors['error_count'] > 0 || $errors['warning_count'] > 0))) {
            throw new InvalidValueException(
                sprintf('入社日が実在する日付ではありません。: %s', $ymd)
            );
        }

        // 2024-02-30 のような繰り上がりを検出する。
        if ($parsed->format(self::FORMAT) !== $normalized) {
            throw new InvalidValueException(
                sprintf('入社日が実在する日付ではありません。: %s', $ymd)
            );
        }

        return new self($parsed);
    }

    public static function fromDateTime(DateTimeInterface $value): self
    {
        return new self(DateTimeImmutable::createFromInterface($value));
    }

    public function value(): DateTimeImmutable
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value->format(self::FORMAT);
    }

    public function equals(self $other): bool
    {
        return $this->toString() === $other->toString();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
