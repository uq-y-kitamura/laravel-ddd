<?php

declare(strict_types=1);

namespace Application\Shared\Transaction;

/**
 * ユースケースからトランザクション境界を宣言するためのポート。
 */
interface TransactionManagerInterface
{
    /**
     * @template T
     *
     * @param  callable(): T  $operation
     * @return T
     */
    public function run(callable $operation): mixed;
}
