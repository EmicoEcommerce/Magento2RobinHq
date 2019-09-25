<?php

namespace Emico\RobinHq\Queue;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class AmqpPublisherFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function create(array $params = [])
    {
        /** @var AmqpPublisher $publisher */
        $publisher = $this->objectManager->create(AmqpPublisher::class, $params);
        $publisher->setPublisher($this->objectManager->get(PublisherInterface::class));
        return $publisher;
    }
}
