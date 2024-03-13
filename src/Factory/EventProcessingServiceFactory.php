<?php


namespace Emico\RobinHq\Factory;


use Emico\RobinHqLib\EventProcessor\CustomerEventProcessor;
use Emico\RobinHqLib\EventProcessor\OrderEventProcessor;
use Emico\RobinHqLib\Service\EventProcessingService;
use Magento\Framework\ObjectManagerInterface;

class EventProcessingServiceFactory
{
    /**
     * EventProcessingServiceFactory constructor.
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(private ObjectManagerInterface $objectManager)
    {
    }

    /**
     * @return EventProcessingService
     */
    public function create(): EventProcessingService
    {
        $eventProcessors = [
            'customer' => $this->objectManager->create(CustomerEventProcessor::class),
            'order'    => $this->objectManager->create(OrderEventProcessor::class),
        ];
        return $this->objectManager->create(EventProcessingService::class, ['eventProcessors' => $eventProcessors]);
    }
}