<?php

namespace Emico\RobinHqTest\DataProvider\DetailView;

use Codeception\Test\Unit;
use Emico\RobinHq\DataProvider\DetailView\NullProvider;
use UnitTester;

class NullProviderTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testNullProviderReturnsNoData(): void
    {
        $dataProvider = new NullProvider();

        $result = $dataProvider->getItems($this->tester->createOrderFixture());

        $this->assertEmpty($result);
    }
}