<?php

namespace Emico\RobinHq\Queue;

use Emico\RobinHqLib\Queue\FileQueue;
use Emico\RobinHqLib\Queue\QueueInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;

class QueueBridge implements QueueInterface
{
    /**
     * @var QueueInterface
     */
    protected $queue;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var EventPublisherFactory
     */
    private $eventPublisherFactory;
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * QueueBridge constructor.
     * @param Manager $moduleManager
     * @param DirectoryList $directoryList
     * @param EventPublisherFactory $eventPublisherFactory
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Manager $moduleManager,
        DirectoryList $directoryList,
        EventPublisherFactory $eventPublisherFactory,
        ObjectManagerInterface $objectManager
    ) {
        $this->moduleManager = $moduleManager;
        $this->directoryList = $directoryList;
        $this->eventPublisherFactory = $eventPublisherFactory;
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritDoc}
     */
    public function pushEvent(string $event): bool
    {
        return $this->getQueueImplementation()->pushEvent($event);
    }

    /**
     * @return QueueInterface
     *
     * Dynamically return right Queue implementation depending on Magento version
     */
    protected function getQueueImplementation(): QueueInterface
    {
        if ($this->queue) {
            return $this->queue;
        }

        if ($this->moduleManager->isEnabled('Magento_MessageQueue') &&
            interface_exists('Magento\Framework\MessageQueue\PublisherInterface')) {
            $this->queue = $this->eventPublisherFactory->create();
        } else {
            $this->queue = $this->objectManager->create(
                FileQueue::class,
                [
                    'directory' => $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/queue'
                ]
            );
        }
        return $this->queue;
    }
}
