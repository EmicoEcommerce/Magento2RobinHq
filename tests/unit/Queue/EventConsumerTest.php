<?php

namespace Emico\RobinHqTest\DataProvider;

use Emico\RobinHq\Queue\EventConsumer;
use Emico\RobinHqLib\Service\EventProcessingService;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mockery;
use Mockery\MockInterface;
use UnitTester;

class EventConsumerTest extends \Codeception\Test\Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var EventProcessingService|MockInterface
     */
    protected $eventProcessingServiceMock;

    /**
     * @var EventConsumer
     */
    protected $eventConsumer;

    public function _before()
    {
        $objectManager = new ObjectManager($this);

        $this->eventProcessingServiceMock = Mockery::spy(EventProcessingService::class);

        $this->eventConsumer = $objectManager->getObject(EventConsumer::class, [
            'eventProcessingService' => $this->eventProcessingServiceMock
        ]);
    }

    public function testRabbitMqMessagesAreDispatchedToProcessingService(): void
    {
        $message = '{"orders":[{"order_number":"123456789","email_address":"info@example.com","name":"First Last","url":null,"revenue":15,"old_revenue":15,"order_date":"2019-08-07T07:48:21+0000","is_first_order":true}]}';
        $this->eventConsumer->processMessage($message);
        $this->eventProcessingServiceMock->shouldHaveReceived('processEvent', [$message]);
    }
}