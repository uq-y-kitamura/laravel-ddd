<?php

declare(strict_types=1);

namespace Infrastructure\Shared\Transaction;

use Application\Shared\Transaction\TransactionManagerInterface;
use Illuminate\Database\DatabaseManager;

/**
 * Laravel のデータベーストランザクションによる実装（アダプタ）。
 */
final readonly class DatabaseTransactionManager implements TransactionManagerInterface
{
    public function __construct(private DatabaseManager $database) {}

    public function run(callable $operation): mixed
    {
        return $this->database->connection()->transaction($operation);
    }
}
