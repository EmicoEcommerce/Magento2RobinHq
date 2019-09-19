<?php

namespace Emico\RobinHqTest\DataProvider;

use Emico\RobinHq\DataProvider\CustomerDataProvider;
use Emico\RobinHq\Mapper\CustomerFactory;
use Emico\RobinHqLib\DataProvider\DataProviderInterface;
use Helper\Unit;
use InvalidArgumentException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Emico\RobinHqLib\Model\Customer as RobinHqCustomerModel;
use Mockery;
use Mockery\MockInterface;
use UnitTester;
use Zend\Diactoros\ServerRequest;

class CustomerDataProviderTest extends \Codeception\Test\Unit
{
    /**
     * @var DataProviderInterface
     */
    protected $dataProvider;

    /**
     * @var UnitTester
     */
    protected $tester;

    public function _before()
    {
        /** @var CustomerRepositoryInterface|MockInterface $customerFactoryMock */
        $customerRepositoryMock = Mockery::mock(CustomerRepositoryInterface::class);
        $customer = $this->tester->createCustomerFixture();
        $customerRepositoryMock->shouldReceive('get')
            ->with(Unit::CUSTOMER_EMAIL)
            ->andReturn($customer);

        /** @var CustomerFactory|MockInterface $customerFactoryMock */
        $customerFactoryMock = Mockery::mock(CustomerFactory::class);
        $customerFactoryMock
            ->shouldReceive('createRobinCustomer')
            ->with($customer)
            ->andReturn(new RobinHqCustomerModel(Unit::CUSTOMER_EMAIL));

        $this->dataProvider = new CustomerDataProvider(
            $customerRepositoryMock,
            $customerFactoryMock
        );
    }

    public function testFetchDataReturnsCustomerData(): void
    {
        $request = new ServerRequest();
        $request = $request->withQueryParams(['email' => Unit::CUSTOMER_EMAIL]);

        $result = $this->dataProvider->fetchData($request);
        $this->assertInstanceOf(RobinHqCustomerModel::class, $result);
    }

    public function testFetchDataThrowsExceptionWhenOmittingEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $request = new ServerRequest();

        $this->dataProvider->fetchData($request);
    }
}