<?php

namespace Emico\RobinHqTest\Queue;

use Codeception\Test\Unit;
use Emico\RobinHq\Queue\AmqpPublisher;
use Emico\RobinHq\Queue\AmqpPublisherFactory;
use Emico\RobinHq\Queue\QueueBridge;
use Emico\RobinHqLib\Queue\FileQueue;
use Emico\RobinHqLib\Queue\QueueInterface;
use Magento\Framework\MessageQueue\ConnectionTypeResolver;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mockery;
use UnitTester;

class QueueBridgeTest extends Unit
{
    /**
     * @var QueueBridge
     */
    protected $queueBridge;

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Manager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $moduleManagerMock;

    /**
     * @var ConnectionTypeResolver|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $connectionTypeResolverMock;

    /**
     * @var FileQueue|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $fileQueueMock;

    public function _before()
    {
        $objectManager = new ObjectManager($this);

        $this->moduleManagerMock = Mockery::mock(Manager::class);
        $this->moduleManagerMock
            ->shouldReceive('isEnabled')
            ->andReturn(false)
            ->byDefault();

        $this->fileQueueMock = Mockery::mock(FileQueue::class);
        $objectManagerMock = Mockery::mock(ObjectManagerInterface::class);
        $objectManagerMock
            ->shouldReceive('create')
            ->with(FileQueue::class, Mockery::any())
            ->andReturn($this->fileQueueMock);

        $this->connectionTypeResolverMock = Mockery::mock(ConnectionTypeResolver::class);
        $this->connectionTypeResolverMock
            ->shouldReceive('getConnectionType')
            ->with('amqp')
            ->andThrow(new \LogicException())
            ->byDefault();

        $objectManagerMock
            ->shouldReceive('get')
            ->with(ConnectionTypeResolver::class)
            ->andReturn($this->connectionTypeResolverMock);

        $amqpPublisherFactoryMock = Mockery::mock(AmqpPublisherFactory::class);
        $amqpPublisherFactoryMock
            ->shouldReceive('create')
            ->andReturn(Mockery::mock(AmqpPublisher::class));

        $this->queueBridge = $objectManager->getObject(
            QueueBridge::class,
            [
                'objectManager' => $objectManagerMock,
                'moduleManager' => $this->moduleManagerMock,
                'amqpPublisherFactory' => $amqpPublisherFactoryMock
            ]
        );
    }

    public function testAmqpQueueCanBeUsed()
    {
        $this->enableMessageQueueModule();

        $this->connectionTypeResolverMock
            ->shouldReceive('getConnectionType')
            ->with('amqp')
            ->andReturn(true);

        $this->assertQueueInstance(AmqpPublisher::class);
    }

    public function testQueueIsCachedWhenRetrievedTwice()
    {
        $this->disabledMessageQueueModule();

        try {
            $instance1 = $this->queueBridge->getQueueImplementation();
            $instance2 = $this->queueBridge->getQueueImplementation();
            $this->assertSame($instance1, $instance2);
        } catch (\Exception $exception) {
            $this->fail('getQueueImplementation threw exception, could not retrieve instance');
        }
    }

    public function testFallbackToFileQueueWhenMessageQueueModuleNotEnabled()
    {
        $this->disabledMessageQueueModule();
        $this->assertQueueInstance(FileQueue::class);
    }

    public function testFallbackToFileQueueWhenAmqpConnectionNotAvailable()
    {
        $this->enableMessageQueueModule();

        $this->connectionTypeResolverMock
            ->shouldReceive('getConnectionType')
            ->with('amqp')
            ->andThrow(new \LogicException());

        $this->assertQueueInstance(FileQueue::class);
    }

    public function testCanPublishMessagesToQueue()
    {
        $eventData = 'foo';

        $this->fileQueueMock
            ->shouldReceive('pushEvent')
            ->once()
            ->with($eventData);

        $this->queueBridge->pushEvent($eventData);
    }

    protected function disabledMessageQueueModule()
    {
        $this->moduleManagerMock
            ->shouldReceive('isEnabled')
            ->with('Magento_MessageQueue')
            ->andReturnFalse();
    }

    protected function enableMessageQueueModule()
    {
        $this->moduleManagerMock
            ->shouldReceive('isEnabled')
            ->with('Magento_MessageQueue')
            ->andReturnTrue();
    }

    /**
     * @param string $expectedInstance
     */
    protected function assertQueueInstance($expectedInstance = QueueInterface::class)
    {
        $instance = null;
        try {
            $instance = $this->queueBridge->getQueueImplementation();
        } catch (\Exception $exception) {
            $this->fail('Not expecting an exception');
        }

        $this->assertInstanceOf($expectedInstance, $instance);
    }
}