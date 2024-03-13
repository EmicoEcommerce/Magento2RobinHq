<?php

namespace Emico\RobinHq\Queue;

use Emico\RobinHq\Factory\EventProcessingServiceFactory;
use Psr\Log\LoggerInterface;

class AmqpConsumer
{
    /**
     * UpdateConsumer constructor.
     *
     * @param EventProcessingServiceFactory $eventProcessingServiceFactory
     * @param LoggerInterface $log
     */
    public function __construct(private EventProcessingServiceFactory $eventProcessingServiceFactory, private LoggerInterface $log)
    {
    }

    /**
     * @param string $message
     */
    public function processMessage(string $message): void
    {
        $eventProcessingService = $this->eventProcessingServiceFactory->create();
        $this->log->debug(sprintf('Handle message (%s)', $message));
        $eventProcessingService->processEvent($message);
    }
}
