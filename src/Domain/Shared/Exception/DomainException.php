<?php

declare(strict_types=1);

namespace Domain\Shared\Exception;

use RuntimeException;

/**
 * ドメイン層で発生する例外の基底クラス。
 * フレームワークに依存せず、プレゼンテーション層で HTTP ステータスへ変換する。
 */
abstract class DomainException extends RuntimeException {}
