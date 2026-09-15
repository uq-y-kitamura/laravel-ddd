<?php

declare(strict_types=1);

namespace Domain\Shared\Exception;

/**
 * 一意制約違反（重複）の例外。HTTP 409 に対応する。
 */
class DuplicateEntityException extends BusinessRuleViolationException {}
