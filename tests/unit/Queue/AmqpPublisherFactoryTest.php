<?php

namespace Emico\RobinHqTest\Queue;

use Codeception\Test\Unit;
use Emico\RobinHq\Cron\FileQueueConsumer;
use Emico\RobinHq\Queue\AmqpPublisher;
use Emico\RobinHq\Queue\AmqpPublisherFactory;
use Emico\RobinHq\Queue\QueueBridge;
use Emico\RobinHqLib\Queue\FileQueue;
use Emico\RobinHqLib\Queue\QueueInterface;
use Magento\Framework\MessageQueue\ConnectionTypeResolver;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mockery;
use UnitTester;

class AmqpPublisherFactoryTest extends Unit
{
    public function testCanCreateAmqpPublisher()
    {
        $amqpPublisherMock = Mockery::mock(AmqpPublisher::class);

        $magentoPublisher = Mockery::mock(PublisherInterface::class);
        $amqpPublisherMock
            ->shouldReceive('setPublisher')
            ->once()
            ->with($magentoPublisher);

        $objectManagerMock = Mockery::mock(ObjectManagerInterface::class);
        $objectManagerMock
            ->shouldReceive('create')
            ->once()
            ->with(AmqpPublisher::class, Mockery::any())
            ->andReturn($amqpPublisherMock);

        $objectManagerMock
            ->shouldReceive('get')
            ->with(PublisherInterface::class)
            ->andReturn($magentoPublisher);

        $factory = new AmqpPublisherFactory($objectManagerMock);

        $instance = $factory->create([]);

        $this->assertEquals($amqpPublisherMock, $instance);
    }
}