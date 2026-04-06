<?php

declare(strict_types=1);

namespace App\Service\Cache\Idempotency;

interface IdempotencyKeyCacheInterface
{
    public function get(string $key): mixed;
}
