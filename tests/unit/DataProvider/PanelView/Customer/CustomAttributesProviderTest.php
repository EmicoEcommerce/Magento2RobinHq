<?php

namespace Emico\RobinHqTest\DataProvider\PanelView\Customer;

use Emico\RobinHq\DataProvider\DetailView\OrderDetailViewProvider;
use Emico\RobinHq\DataProvider\EavAttribute\AttributeRetriever;
use Emico\RobinHq\DataProvider\EavAttribute\AttributeValue;
use Emico\RobinHq\DataProvider\PanelView\Customer\CustomAttributesProvider;
use Emico\RobinHq\Model\Config;
use Emico\RobinHqLib\Model\Order\DetailsView;
use Helper\Unit;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Mockery;
use UnitTester;

class CustomAttributesProviderTest extends \Codeception\Test\Unit
{
    /**
     * @var CustomAttributesProvider
     */
    protected $dataProvider;

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Config|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $moduleConfig;

    /**
     * @var \Magento\Eav\Model\Config|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $eavConfig;

    public function _before()
    {
        $objectManager = new ObjectManager($this);

        $this->moduleConfig = Mockery::mock(Config::class);

        $this->eavConfig = Mockery::mock(\Magento\Eav\Model\Config::class);

        $this->dataProvider = $objectManager->getObject(CustomAttributesProvider::class, [
            'moduleConfig' => $this->moduleConfig,
            'eavConfig' => $this->eavConfig
        ]);
    }

    public function testNoCustomAttributesAreRetrievedWhenNotConfigured()
    {
        $this->moduleConfig
            ->shouldReceive('getCustomerAttributes')
            ->andReturn([]);

        $result = $this->dataProvider->getData($this->tester->createCustomerFixture());

        $this->assertEquals([], $result);
    }

    public function testRetrieveCustomAttributes()
    {
        $this->moduleConfig
            ->shouldReceive('getCustomerAttributes')
            ->andReturn(['custom_attribute1', 'custom_attribute2', 'custom_attribute3']);

        $this->eavConfig
            ->shouldReceive('getAttribute')
            ->andReturnUsing(function($type, $code) {
                $attribute = Mockery::mock(AttributeInterface::class);
                $attribute
                    ->shouldReceive('getDefaultFrontendLabel')
                    ->andReturn($code . '_label');
                return $attribute;
            })
            ->byDefault();

        $this->eavConfig
            ->shouldReceive('getAttribute')
            ->with(Mockery::any(), 'custom_attribute3')
            ->andThrow(new LocalizedException(__('')));

        $result = $this->dataProvider->getData($this->tester->createCustomerFixture([
            '__toArray' => [
                'custom_attribute1' => 'val1',
                'custom_attributes' => [
                    'custom_attribute2' => [
                        'value' => 'val2'
                    ]
                ]
            ]]
        ));

        $this->assertEquals(
            [
                'custom_attribute1_label' => 'val1',
                'custom_attribute2_label' => 'val2',
            ],
            $result
        );
    }

    public function testNoCustomAttributesAreRetrievedWhenNoCustomerModel()
    {
        $result = $this->dataProvider->getData($this->tester->createCustomerFixture([], CustomerInterface::class));

        $this->assertEquals([], $result);
    }
}