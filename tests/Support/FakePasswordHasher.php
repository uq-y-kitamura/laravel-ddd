<?php

declare(strict_types=1);

namespace Tests\Support;

use Domain\User\Service\PasswordHasherInterface;
use Domain\User\ValueObject\HashedPassword;
use Domain\User\ValueObject\PlainPassword;

/**
 * ユニットテスト用の単純なパスワードハッシャー。
 * 実際のハッシュ関数は使わず、検証しやすい決定的な値を返す。
 */
final class FakePasswordHasher implements PasswordHasherInterface
{
    public const PREFIX = 'hashed:';

    public function hash(PlainPassword $password): HashedPassword
    {
        return new HashedPassword(self::PREFIX.$password->value());
    }

    public function verify(PlainPassword $password, HashedPassword $hashed): bool
    {
        return $hashed->value() === self::PREFIX.$password->value();
    }
}
