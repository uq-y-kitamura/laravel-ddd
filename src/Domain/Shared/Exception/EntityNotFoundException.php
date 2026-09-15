<?php

declare(strict_types=1);

namespace Domain\Shared\Exception;

/**
 * 集約が見つからない場合の例外。HTTP 404 に対応する。
 */
class EntityNotFoundException extends DomainException {}
