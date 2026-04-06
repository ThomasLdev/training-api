<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * @implements ProcessorInterface<object, object>
 */
#[AsDecorator('api_platform.doctrine.orm.state.persist_processor')]
readonly class SafePersistProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $decorated,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        try {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        } catch (UniqueConstraintViolationException $e) {
            throw new ConflictHttpException('Resource already exists.', $e);
        } catch (\Throwable $e) {
            if ($e->getPrevious() instanceof UniqueConstraintViolationException) {
                throw new ConflictHttpException('Resource already exists.', $e);
            }
            throw $e;
        }
    }
}
