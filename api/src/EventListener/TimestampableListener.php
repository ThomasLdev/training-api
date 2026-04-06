<?php

namespace App\EventListener;

use App\Entity\TimestampableInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class TimestampableListener
{
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof TimestampableInterface) {
            return;
        }

        $now = new \DateTimeImmutable();
        $entity->setCreatedAt($now);
        $entity->setUpdatedAt($now);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof TimestampableInterface) {
            return;
        }

        $entity->setUpdatedAt(new \DateTimeImmutable());
    }
}
