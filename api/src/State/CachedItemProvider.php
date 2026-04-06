<?php

namespace App\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @implements ProviderInterface<object>
 */
class CachedItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProviderInterface $decorated,
        private readonly CacheInterface $cache,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        if (!$operation instanceof Get) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        $class = $operation->getClass();
        $key = str_replace('\\', '_', $class) . '_' . implode('_', array_map('strval', $uriVariables));

        return $this->cache->get($key, function (ItemInterface $item) use ($operation, $uriVariables, $context): ?object {
            $item->expiresAfter(300); // 5 minutes

            return $this->decorated->provide($operation, $uriVariables, $context);
        });
    }
}
