<?php

declare(strict_types=1);

namespace Domain\Shared\Exception;

/**
 * 値オブジェクト・エンティティの不変条件違反。HTTP 422 に対応する。
 */
final class InvalidValueException extends DomainException {}
