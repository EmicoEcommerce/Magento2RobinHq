<?php

namespace Emico\RobinHqTest\Cron;

use Codeception\Test\Unit;
use Emico\RobinHq\Cron\FileQueueConsumer;
use Emico\RobinHq\Queue\QueueBridge;
use Emico\RobinHqLib\Queue\FileQueue;
use Emico\RobinHqLib\Queue\QueueInterface;
use Mockery;
use Mockery\MockInterface;
use UnitTester;

class FileQueueConsumerTest extends Unit
{
    /**
     * @var FileQueueConsumer
     */
    protected $fileQueueConsumer;

    /**
     * @var UnitTester
     */
    protected $tester;
    /**
     * @var QueueBridge|Mockery\LegacyMockInterface|MockInterface
     */
    private $queueBridgeMock;

    public function _before()
    {
        $this->queueBridgeMock = Mockery::mock(QueueBridge::class);
        $this->queueBridgeMock
            ->shouldReceive('getQueueImplementation')
            ->andReturn(Mockery::mock(QueueInterface::class))
            ->byDefault();
        $this->fileQueueConsumer = new FileQueueConsumer($this->queueBridgeMock);
    }

    public function _after()
    {
        Mockery::close();
    }

    public function testCanProcessFileQueue()
    {
        $queueMock = Mockery::mock(FileQueue::class);
        $queueMock
            ->shouldReceive('processQueue')
            ->once();

        $this->queueBridgeMock
            ->shouldReceive('getQueueImplementation')
            ->andReturn($queueMock);

        $this->fileQueueConsumer->execute();
    }

    public function testOtherQueueTypesAreNotProcessed()
    {
        $queueMock = Mockery::mock(QueueInterface::class);
        $queueMock
            ->shouldReceive('processQueue')
            ->never();

        $this->queueBridgeMock
            ->shouldReceive('getQueueImplementation')
            ->andReturn($queueMock);

        $this->fileQueueConsumer->execute();
        Mockery::close();
    }
}