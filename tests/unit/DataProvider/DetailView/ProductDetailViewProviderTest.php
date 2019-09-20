<?php

namespace Emico\RobinHqTest\DataProvider\DetailView;

use Emico\RobinHq\DataProvider\DetailView\OrderDetailViewProvider;
use Emico\RobinHq\DataProvider\DetailView\ProductDataProviderInterface;
use Emico\RobinHq\DataProvider\DetailView\ProductDetailViewProvider;
use Emico\RobinHq\DataProvider\EavAttribute\AttributeRetriever;
use Emico\RobinHq\DataProvider\EavAttribute\AttributeValue;
use Emico\RobinHq\Model\Config;
use Emico\RobinHqLib\Model\Order\DetailsView;
use Helper\Unit;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mockery;
use UnitTester;

class ProductDetailViewProviderTest extends \Codeception\Test\Unit
{
    /**
     * @var OrderDetailViewProvider
     */
    protected $dataProvider;

    /**
     * @var Config|Mockery\MockInterface
     */
    protected $moduleConfig;

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var AttributeRetriever|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    protected $attributeRetriever;

    /**
     * @var ProductDataProviderInterface|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    protected $productDataProvider;

    public function _before()
    {
        $objectManager = new ObjectManager($this);

        $priceCurrencyFormatter = Mockery::mock(PriceCurrencyInterface::class);
        $priceCurrencyFormatter
            ->shouldReceive('format')
            ->andReturnUsing(function(string $price) {
               return 'EUR ' . $price;
            });

        $this->moduleConfig = Mockery::mock(Config::class);
        $this->moduleConfig
            ->shouldReceive('getProductAttributes')
            ->andReturn([])
            ->byDefault();

        $this->attributeRetriever = Mockery::mock(AttributeRetriever::class)
            ->shouldReceive('getAttributeValue')
            ->andReturnNull()
            ->byDefault()
            ->getMock();

        $this->productDataProvider = Mockery::mock(ProductDataProviderInterface::class);
        $this->productDataProvider
            ->shouldReceive('getAdditionalProductData')
            ->andReturn([])
            ->byDefault();

        $this->dataProvider = $objectManager->getObject(ProductDetailViewProvider::class, [
            'priceCurrency' => $priceCurrencyFormatter,
            'moduleConfig' => $this->moduleConfig,
            'attributeRetriever' => $this->attributeRetriever,
            'productDataProvider' => $this->productDataProvider
        ]);
    }

    public function testCanExtraBasicOrderItemData(): void
    {
        $orderFixture = $this->tester->createOrderFixture();

        $items = $this->dataProvider->getItems($orderFixture);

        $productData = $this->getProductDataFromResponse($items);

        $this->assertEquals(
            [
                'artikelnr_(SKU)' => Unit::PRODUCT_SKU,
                'article name' => Unit::PRODUCT_NAME,
                'quantity' => Unit::ORDERITEM_QUANTITY,
                'price' => 'EUR 9',
                'totalIncludingTax' => 'EUR 18'
            ],
            $productData
        );
    }

    public function testCanExtractCustomProductAttributes()
    {
        $customAttributeCode = 'my_custom_attr';
        $customAttributeValue = 'my_custom_attr_value';

        $this->moduleConfig
            ->shouldReceive('getProductAttributes')
            ->andReturn([$customAttributeCode]);

        $this->attributeRetriever
            ->shouldReceive('getAttributeValue')
            ->once()
            ->with(Product::ENTITY, Mockery::any(), $customAttributeCode)
            ->andReturn(new AttributeValue($customAttributeCode, $customAttributeValue));

        $orderFixture = $this->tester->createOrderFixture();

        $items = $this->dataProvider->getItems($orderFixture);

        $productData = $this->getProductDataFromResponse($items);

        $this->assertArrayHasKey($customAttributeCode, $productData);
        $this->assertEquals($customAttributeValue, $productData[$customAttributeCode]);
    }

    public function testCanAddAdditionalProductDataToResponse()
    {
        $this->productDataProvider
            ->shouldReceive('getAdditionalProductData')
            ->andReturn(['foo' => 'bar']);

        $orderFixture = $this->tester->createOrderFixture();

        $items = $this->dataProvider->getItems($orderFixture);

        $productData = $this->getProductDataFromResponse($items);

        $this->assertArrayHasKey('foo', $productData);
        $this->assertEquals('bar', $productData['foo']);
    }

    public function testCustomAttributesAreOnlyRetrievedForProductModels()
    {
        $orderFixture = $this->tester->createOrderFixture([
            'getItems' => [
                $this->tester->createOrderItemFixture([
                    'getProduct' => $this->tester->createProductFixture(ProductInterface::class)
                ])
            ]
        ]);

        $this->moduleConfig
            ->shouldReceive('getProductAttributes')
            ->andReturn(['foo']);

        $this->attributeRetriever
            ->shouldReceive('getAttributeValue')
            ->never();

        $this->dataProvider->getItems($orderFixture);
    }

    /**
     * @param array $detailViews
     * @return array
     */
    protected function getProductDataFromResponse(array $detailViews): array
    {
        $this->assertCount(1, $detailViews);

        /** @var DetailsView $detailView */
        $detailView = current($detailViews);
        $this->assertInstanceOf(DetailsView::class, $detailView);
        $this->assertEquals(DetailsView::DISPLAY_MODE_ROWS, $detailView->getDisplayAs());
        $this->assertEquals('products', $detailView->getCaption());
        $data = $detailView->getData();

        $this->assertCount(1, $data);

        $productData = $data[0];
        return $productData;
    }
}