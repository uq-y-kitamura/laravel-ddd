<?php

declare(strict_types=1);

namespace App\Providers;

use Application\Shared\Transaction\TransactionManagerInterface;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Domain\User\Repository\UserRepositoryInterface;
use Domain\User\Service\PasswordHasherInterface;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Persistence\Eloquent\Repository\EloquentEmployeeRepository;
use Infrastructure\Persistence\Eloquent\Repository\EloquentUserRepository;
use Infrastructure\Security\BcryptPasswordHasher;
use Infrastructure\Shared\Transaction\DatabaseTransactionManager;

/**
 * ドメイン層が定義したポート（インターフェース）に、インフラ層のアダプタを束ねる。
 * 依存性逆転の結節点であり、フレームワークを知ってよい唯一の層。
 */
final class DomainServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        TransactionManagerInterface::class => DatabaseTransactionManager::class,
        EmployeeRepositoryInterface::class => EloquentEmployeeRepository::class,
        UserRepositoryInterface::class => EloquentUserRepository::class,
        PasswordHasherInterface::class => BcryptPasswordHasher::class,
    ];
}
