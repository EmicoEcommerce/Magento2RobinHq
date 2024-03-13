<?php

declare(strict_types=1);

namespace Emico\RobinHq\Cron;

use Emico\RobinHq\Queue\QueueBridge;
use Emico\RobinHqLib\Queue\FileQueue;

class FileQueueConsumer
{
    /**
     * FileQueueConsumer constructor.
     * @param QueueBridge $queueBridge
     */
    public function __construct(private QueueBridge $queueBridge)
    {
    }

    /**
     * Process the RobinHQ file queue
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
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