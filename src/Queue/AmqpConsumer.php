<?php

namespace Emico\RobinHq\Queue;

use Emico\RobinHqLib\Service\EventProcessingService;
use Psr\Log\LoggerInterface;

class AmqpConsumer
{
    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var EventProcessingService
     */
    private $eventProcessingService;

    /**
     * UpdateConsumer constructor.
     *
     * @param EventProcessingService $eventProcessingService
     * @param LoggerInterface $log
     */
    public function __construct(EventProcessingService $eventProcessingService, LoggerInterface $log)
    {
        $this->log = $log;
        $this->eventProcessingService = $eventProcessingService;
    }

    /**
     * @param string $message
     */
    public function processMessage(string $message): void
    {
        $this->log->debug(sprintf('Handle message (%s)', $message));
        $this->eventProcessingService->processEvent($message);
    }
}
