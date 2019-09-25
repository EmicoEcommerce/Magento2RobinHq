<?php

namespace Emico\RobinHq\Queue;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class EventPublisherFactory
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
        return $this->objectManager->create(PublisherInterface::class, $params);
    }
}
