<?php

namespace Emico\RobinHqTest\Queue;

use Codeception\Test\Unit;
use Emico\RobinHq\Queue\AmqpPublisher;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mockery;
use Mockery\MockInterface;
use UnitTester;

class AmqpPublisherTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var AmqpPublisher
     */
    private $eventPublisher;

    /**
     * @var PublisherInterface|Mockery\LegacyMockInterface|MockInterface
     */
    private $publisher;

    public function _before()
    {
        $objectManager = new ObjectManager($this);

        $this->publisher = Mockery::spy(PublisherInterface::class);

        $this->eventPublisher = $objectManager->getObject(AmqpPublisher::class, [
            'publisher' => $this->publisher
        ]);
    }

    public function testEventIsPublishedToInternalPublisher(): void
    {
        $message = '{"orders":[{"order_number":"123456789","email_address":"info@example.com","name":"First Last","url":null,"revenue":15,"old_revenue":15,"order_date":"2019-08-07T07:48:21+0000","is_first_order":true}]}';
        $result = $this->eventPublisher->pushEvent($message);

        $this->publisher
            ->shouldHaveReceived('publish')
            ->once()
            ->with(Mockery::any(), $message);

        $this->assertTrue($result);
    }
}