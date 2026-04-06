<?php

namespace App\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Contracts\Cache\CacheInterface;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class InvalidateCacheListener
{
    /** @var list<string> */
    private array $keysToInvalidate = [];

    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->collectKey($entity);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->collectKey($entity);
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        foreach ($this->keysToInvalidate as $key) {
            $this->cache->delete($key);
        }

        $this->keysToInvalidate = [];
    }

    private function collectKey(object $entity): void
    {
        $class = str_replace('\\', '_', $entity::class);

        if (method_exists($entity, 'getUuid')) {
            $this->keysToInvalidate[] = $class . '_' . $entity->getUuid();
        } elseif (method_exists($entity, 'getId') && $entity->getId() !== null) {
            $this->keysToInvalidate[] = $class . '_' . $entity->getId();
        }
    }
}
