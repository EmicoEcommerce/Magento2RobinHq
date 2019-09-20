<?php

namespace Emico\RobinHqTest\DataProvider\DetailView;

use Emico\RobinHq\DataProvider\DetailView\TotalDetailViewProvider;
use Emico\RobinHqLib\Model\Order\DetailsView;
use Helper\Unit;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mockery;
use UnitTester;

class TotalDetailViewProviderTest extends \Codeception\Test\Unit
{
    /**
     * @var TotalDetailViewProvider
     */
    protected $dataProvider;

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var PriceCurrencyInterface|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $priceCurrencyFormatter;

    public function _before()
    {
        $objectManager = new ObjectManager($this);

        $this->priceCurrencyFormatter = Mockery::mock(PriceCurrencyInterface::class);
        $this->priceCurrencyFormatter
            ->shouldReceive('format')
            ->andReturnUsing(function(string $price) {
                return $price;
            });

        $this->dataProvider = $objectManager->getObject(TotalDetailViewProvider::class, [
            'priceCurrency' => $this->priceCurrencyFormatter
        ]);
    }

    public function testCanRetrieveTotalsDataFromOrder()
    {
        $result = $this->dataProvider->getItems($this->tester->createOrderFixture());

        $this->assertIsArray($result);

        /** @var DetailsView $detailView */
        $detailView = current($result);
        $this->assertInstanceOf(DetailsView::class, $detailView);
        $this->assertEquals(DetailsView::DISPLAY_MODE_ROWS, $detailView->getDisplayAs());
        $this->assertEquals('totals', $detailView->getCaption());

        $this->assertEquals(
            [
                'subtotal_(incl_VAT)' => Unit::ORDER_SUBTOTAL_INCL_TAX,
                'shippingcost' => Unit::ORDER_SHIPPING_INCL_TAX,
                'discounts_(incl_VAT)' => Unit::ORDER_DISCOUNT_AMOUNT,
                'VAT' => Unit::ORDER_TAX_AMOUNT,
                'total_(incl_VAT)' => Unit::ORDER_GRAND_TOTAL,
                'payed' => Unit::ORDER_PAYMENT_AMOUNT_PAID,
                'refunded' => Unit::ORDER_TOTAL_REFUNDED,
                'revenue' => 10
            ],
            $detailView->getData()[0]
        );
    }
}