<?php

namespace Emico\RobinHq\Queue;

use Emico\RobinHqLib\Queue\FileQueue;
use Emico\RobinHqLib\Queue\QueueInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\MessageQueue\ConnectionTypeResolver;
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
     * @var AmqpPublisherFactory
     */
    private $amqpPublisherFactory;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * QueueBridge constructor.
     * @param Manager $moduleManager
     * @param DirectoryList $directoryList
     * @param AmqpPublisherFactory $amqpPublisherFactory
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Manager $moduleManager,
        DirectoryList $directoryList,
        AmqpPublisherFactory $amqpPublisherFactory,
        ObjectManagerInterface $objectManager
    ) {
        $this->moduleManager = $moduleManager;
        $this->directoryList = $directoryList;
        $this->amqpPublisherFactory = $amqpPublisherFactory;
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
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getQueueImplementation(): QueueInterface
    {
        if ($this->queue) {
            return $this->queue;
        }

        if ($this->isAmqpMessageQueueAvailable()) {
            $this->queue = $this->amqpPublisherFactory->create();
            return $this->queue;
        }

        // Fallback to file queue when requirements for AMQP queueing are not matched
        $this->queue = $this->objectManager->create(
            FileQueue::class,
            [
                'directory' => $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/queue'
            ]
        );
        return $this->queue;
    }

    /**
     * @return bool
     */
    protected function isAmqpMessageQueueAvailable(): bool
    {
        if ($this->moduleManager->isEnabled('Magento_MessageQueue') &&
            interface_exists('Magento\Framework\MessageQueue\PublisherInterface')) {

            /** @var ConnectionTypeResolver $connectionTypeResolver */
            $connectionTypeResolver = $this->objectManager->get(ConnectionTypeResolver::class);
            try {
                $connectionTypeResolver->getConnectionType('amqp');
                return true;
            } catch (\LogicException $exception) {
                return false;
            }
        }
        return false;
    }
}
