<?php

declare(strict_types=1);

namespace Infrastructure\Security;

use Domain\User\Service\PasswordHasherInterface;
use Domain\User\ValueObject\HashedPassword;
use Domain\User\ValueObject\PlainPassword;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * Laravel のハッシュ機能（既定は bcrypt）を用いた PasswordHasherInterface の実装。
 */
final readonly class BcryptPasswordHasher implements PasswordHasherInterface
{
    public function __construct(private Hasher $hasher) {}

    public function hash(PlainPassword $password): HashedPassword
    {
        return new HashedPassword($this->hasher->make($password->value()));
    }

    public function verify(PlainPassword $password, HashedPassword $hashed): bool
    {
        return $this->hasher->check($password->value(), $hashed->value());
    }
}
