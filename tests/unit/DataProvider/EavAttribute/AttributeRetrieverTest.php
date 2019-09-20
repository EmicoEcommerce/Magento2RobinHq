<?php

namespace Emico\RobinHqTest\DataProvider\EavAttribute;

use Codeception\Test\Unit;
use Emico\RobinHq\DataProvider\EavAttribute\AttributeRetriever;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Mockery;
use UnitTester;

class AttributeRetrieverTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Config|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $eavConfigMock;

    /**
     * @var AttributeRetriever
     */
    private $attributeRetriever;

    public function _before()
    {
        $attributeMock = Mockery::mock(
            AttributeInterface::class,
            ['getDefaultFrontendLabel' => 'frontend label']
        );

        $this->eavConfigMock = Mockery::mock(Config::class);
        $this->eavConfigMock
            ->shouldReceive('getAttribute')
            ->andReturn($attributeMock)
            ->byDefault();
        $this->attributeRetriever = new AttributeRetriever($this->eavConfigMock);
    }

    public function testGetAttributeValueUsesGetAttributeText(): void
    {
        $model = Mockery::mock(Product::class);
        $model->allows(
            [
                'getAttributeText' => 'attribute value',
                'getData' => 'attribute value2'
            ]
        );

        $value = $this->attributeRetriever->getAttributeValue(Product::ENTITY, $model, 'code');

        $this->assertEquals('frontend label', $value->getLabel());
        $this->assertEquals('attribute value', $value->getValue());
    }

    public function testGetAttributeValueUsesGetData(): void
    {
        $model = Mockery::mock(Order::class);
        $model->allows(['getData' => 'attribute value']);

        $value = $this->attributeRetriever->getAttributeValue(Order::ENTITY, $model, 'code');

        $this->assertEquals('frontend label', $value->getLabel());
        $this->assertEquals('attribute value', $value->getValue());
    }

    public function testGetAttributeReturnsNullOnException()
    {
        $this->eavConfigMock
            ->shouldReceive('getAttribute')
            ->andThrow(new LocalizedException(__()));

        $model = Mockery::mock(Order::class);
        $model->allows(['getData' => 'attribute value']);

        $value = $this->attributeRetriever->getAttributeValue(Order::ENTITY, $model, 'code');

        $this->assertNull($value);
    }
}