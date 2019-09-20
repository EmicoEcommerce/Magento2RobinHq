<?php

namespace Emico\RobinHqTest\DataProvider\DetailView;

use Codeception\Test\Unit;
use Emico\RobinHq\DataProvider\DetailView\NullProductDataProvider;
use UnitTester;

class NullProductDataProviderTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testNullProviderReturnsNoData(): void
    {
        $dataProvider = new NullProductDataProvider();

        $result = $dataProvider->getAdditionalProductData($this->tester->createProductFixture());

        $this->assertEmpty($result);
    }
}