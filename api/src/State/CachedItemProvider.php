<?php

namespace App\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @implements ProviderInterface<object>
 */
readonly class CachedItemProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')]
        private ProviderInterface $decorated,
        #[Autowire(service: 'cache.app')]
        private CacheInterface $cache,
        private EntityManagerInterface $em,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        if (!$operation instanceof Get) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        $class = $operation->getClass();
        $key = str_replace('\\', '_', $class) . '_' . implode('_', array_map('strval', $uriVariables));

        $entity = $this->cache->get($key, function (ItemInterface $item) use ($operation, $uriVariables, $context): ?object {
            $item->expiresAfter(300);

            return $this->decorated->provide($operation, $uriVariables, $context);
        });

        if ($entity !== null && !$this->em->contains($entity)) {
            $id = $this->em->getClassMetadata($entity::class)->getIdentifierValues($entity);
            $entity = $this->em->find($entity::class, $id);
        }

        return $entity;
    }
}
