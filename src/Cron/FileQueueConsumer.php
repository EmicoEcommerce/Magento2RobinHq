<?php

namespace Emico\RobinHq\Cron;

use Emico\RobinHq\Queue\QueueBridge;
use Emico\RobinHqLib\Queue\FileQueue;

class FileQueueConsumer
{
    /**
     * @var QueueBridge
     */
    private $queueBridge;

    /**
     * FileQueueConsumer constructor.
     * @param QueueBridge $queueBridge
     */
    public function __construct(QueueBridge $queueBridge)
    {
        $this->queueBridge = $queueBridge;
    }

    /**
     * Cron job method to clean old cache resources
     *
     * @return void
     */
    public function execute()
    {
        $queueImplementation = $this->queueBridge->getQueueImplementation();
        if (!$queueImplementation instanceof FileQueue) {
            return;
        }

        $queueImplementation->processQueue();
    }
}