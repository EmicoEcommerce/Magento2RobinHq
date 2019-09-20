<?php

namespace Emico\RobinHqTest\Model\Config\Source;

use Codeception\Test\Unit;
use Magento\Customer\Model\Customer;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Emico\RobinHq\Model\Config\Source\Attributes;
use Mockery;
use UnitTester;

class AttributesTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testEavAttributesAreRetrievable(): void
    {
        $attributes = [
            $this->getAttribute('attr1', 'label1'),
            $this->getAttribute('attr2', 'label2'),
            $this->getAttribute('attr3', 'label3'),
        ];

        $eavConfigMock = Mockery::mock(Config::class);
        $eavConfigMock
            ->shouldReceive('getEntityAttributes')
            ->once()
            ->with(Customer::ENTITY)
            ->andReturn($attributes);

        $sourceModel = new Attributes($eavConfigMock);
        $options = $sourceModel->toOptionArray();

        $this->assertCount(3, $options);
        $this->assertEquals('attr1', $options[0]['value']);
        $this->assertEquals('label1 [attr1]', $options[0]['label']);
    }

    /**
     * @param string $code
     * @param string $frontendLabel
     * @return AttributeInterface
     */
    protected function getAttribute(string $code, string $frontendLabel): AttributeInterface
    {
        return Mockery::mock(
            AttributeInterface::class,
            [
                'getAttributeCode' => $code,
                'getDefaultFrontendLabel' => $frontendLabel
            ]
        );
    }
}