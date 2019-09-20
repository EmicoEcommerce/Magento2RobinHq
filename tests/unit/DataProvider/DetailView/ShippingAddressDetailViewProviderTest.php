<?php

namespace Emico\RobinHqTest\DataProvider\DetailView;

use Emico\RobinHq\DataProvider\DetailView\ShippingAddressDetailViewProvider;
use Emico\RobinHqLib\Model\Order\DetailsView;
use Helper\Unit;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Directory\Api\Data\CountryInformationInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mockery;
use UnitTester;

class ShippingAddressDetailViewProviderTest extends \Codeception\Test\Unit
{
    /**
     * @var ShippingAddressDetailViewProvider
     */
    protected $dataProvider;

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var CountryInformationAcquirerInterface|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $countryInformationAcquirer;

    public function _before()
    {
        $objectManager = new ObjectManager($this);

        $this->countryInformationAcquirer = Mockery::mock(CountryInformationAcquirerInterface::class);

        $this->dataProvider = $objectManager->getObject(ShippingAddressDetailViewProvider::class, [
            'countryInformationAcquirer' => $this->countryInformationAcquirer
        ]);
    }

    public function testCanRetrieveShippingAddressInformation()
    {
        $countryName = 'Nederland';
        $this->countryInformationAcquirer
            ->shouldReceive('getCountryInfo')
            ->once()
            ->with(Unit::ADDRESS_COUNTRY_ID)
            ->andReturn(Mockery::mock(CountryInformationInterface::class, ['getFullNameLocale' => $countryName]));

        $orderFixture = $this->tester->createOrderFixture();

        $result = $this->dataProvider->getItems($orderFixture);

        $this->assertIsArray($result);
        /** @var DetailsView $detailView */
        $detailView = current($result);
        $this->assertInstanceOf(DetailsView::class, $detailView);
        $this->assertEquals(DetailsView::DISPLAY_MODE_DETAILS, $detailView->getDisplayAs());

        $this->assertEquals(
            [
                'name' => Unit::CUSTOMER_FULLNAME,
                'address' => implode(' ', Unit::ADDRESS_STREET),
                'postalcode + city' => Unit::ADDRESS_POSTCODE . ' ' . Unit::ADDRESS_CITY,
                'country' => $countryName
            ],
            $detailView->getData()
        );
    }

    public function testNoDataIsReturnedWhenNoShippingAddressAvailable()
    {
        $orderFixture = $this->tester->createOrderFixture(['getShippingAddress' => null]);

        $result = $this->dataProvider->getItems($orderFixture);

        $this->assertEquals([], $result);
    }
}