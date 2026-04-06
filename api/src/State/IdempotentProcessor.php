<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @implements ProcessorInterface<object, object>
 */
readonly class IdempotentProcessor implements ProcessorInterface
{
    public const string IDEMPOTENCY_KEY_HEADER = 'Idempotency-Key';

    public function __construct(
        private RequestStack $requestStack,
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $decorated,
        private CacheInterface $cache,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$operation instanceof Post) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        $idempotencyKey = $this->requestStack
            ->getCurrentRequest()
            ->headers
            ->get(self::IDEMPOTENCY_KEY_HEADER)
        ;

        if (null === $idempotencyKey) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        $cacheKey = sprintf('idempotency_%s', hash('xxh3', $operation->getClass() . $idempotencyKey));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($data, $operation, $uriVariables, $context) {
            $item->expiresAfter(3600);
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        });
    }
}
