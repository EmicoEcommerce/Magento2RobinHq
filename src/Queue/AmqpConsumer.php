<?php

namespace Emico\RobinHq\Queue;

use Emico\RobinHq\Factory\EventProcessingServiceFactory;
use Psr\Log\LoggerInterface;

class AmqpConsumer
{
    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var EventProcessingServiceFactory
     */
    private $eventProcessingServiceFactory;

    /**
     * UpdateConsumer constructor.
     *
     * @param EventProcessingServiceFactory $eventProcessingServiceFactory
     * @param LoggerInterface $log
     */
    public function __construct(EventProcessingServiceFactory $eventProcessingServiceFactory, LoggerInterface $log)
    {
        $this->log = $log;
        $this->eventProcessingServiceFactory = $eventProcessingServiceFactory;
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
