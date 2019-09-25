<?php

namespace Emico\RobinHq\Queue;

use Emico\RobinHqLib\Queue\QueueInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class AmqpPublisher implements QueueInterface
{
    /**
     * Topic name
     */
    private const TOPIC_NAME = 'emico.robinhq';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param PublisherInterface $publisher
     */
    public function setPublisher(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * @param string $event
     * @return bool
     */
    public function pushEvent(string $event): bool
    {
        $this->publisher->publish(self::TOPIC_NAME, $event);
        return true;
    }
}
