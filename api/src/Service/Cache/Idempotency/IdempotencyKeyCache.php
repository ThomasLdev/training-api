<?php

declare(strict_types=1);

namespace App\Service\Cache\Idempotency;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class IdempotencyKeyCache implements IdempotencyKeyCacheInterface
{
    private const string IDEMPOTENCY_KEY_FORMAT = 'idempotency_%s_%s';

    public function __construct(
        private CacheInterface $cache,
    )
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function get(string $key): mixed
    {
        return $this->cache->get($key, );
    }
}
