<?php

declare(strict_types=1);

namespace Domain\User\Service;

use Domain\User\ValueObject\HashedPassword;
use Domain\User\ValueObject\PlainPassword;

/**
 * パスワードのハッシュ化・照合を担うドメインサービスのポート。
 * 実装（アダプタ）はインフラ層に置く。
 */
interface PasswordHasherInterface
{
    /**
     * 平文パスワードをハッシュ化する。
     */
    public function hash(PlainPassword $password): HashedPassword;

    /**
     * 平文パスワードがハッシュと一致するかを検証する。
     */
    public function verify(PlainPassword $password, HashedPassword $hashed): bool;
}
