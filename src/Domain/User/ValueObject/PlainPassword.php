<?php

declare(strict_types=1);

namespace Domain\User\ValueObject;

use Domain\Shared\Exception\InvalidValueException;
use SensitiveParameter;

/**
 * 平文パスワードを表す値オブジェクト。
 *
 * 永続化・ログ出力を避けるため、意図的に __toString() は実装しない。
 */
final class PlainPassword
{
    public const MIN_LENGTH = 8;

    /** bcrypt が扱えるのは 72 バイトまでのため、それを上限とする。 */
    public const MAX_BYTES = 72;

    private readonly string $value;

    public function __construct(#[SensitiveParameter] string $value)
    {
        // パスワードは前後の空白も有効な文字であるため trim しない。
        if (mb_strlen($value) < self::MIN_LENGTH) {
            throw new InvalidValueException(
                sprintf('パスワードは %d 文字以上で入力してください。', self::MIN_LENGTH)
            );
        }

        if (strlen($value) > self::MAX_BYTES) {
            throw new InvalidValueException(
                sprintf('パスワードは %d バイト以内で入力してください。', self::MAX_BYTES)
            );
        }

        if (preg_match('/[a-zA-Z]/', $value) !== 1) {
            throw new InvalidValueException('パスワードは英字を 1 文字以上含めてください。');
        }

        if (preg_match('/[0-9]/', $value) !== 1) {
            throw new InvalidValueException('パスワードは数字を 1 文字以上含めてください。');
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

    /**
     * var_dump() などで平文が漏れないようにマスクする。
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['value' => '********'];
    }
}
