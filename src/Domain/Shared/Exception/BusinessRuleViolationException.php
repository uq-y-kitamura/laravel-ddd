<?php

declare(strict_types=1);

namespace Domain\Shared\Exception;

/**
 * 入力単体は正しいが、現在の状態と衝突して実行できない操作を表す例外。HTTP 409 に対応する。
 * 一意制約違反や「管理者が 0 人になる操作」などが該当する。
 */
class BusinessRuleViolationException extends DomainException {}
