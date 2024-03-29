<?php

namespace Emico\RobinHqTest\DataProvider;

use Emico\RobinHq\DataProvider\CustomerDataProvider;
use Emico\RobinHq\Mapper\CustomerFactory;
use Emico\RobinHqLib\DataProvider\DataProviderInterface;
use Emico\RobinHqLib\DataProvider\Exception\DataNotFoundException;
use Helper\Unit;
use InvalidArgumentException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Emico\RobinHqLib\Model\Customer as RobinHqCustomerModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Mockery;
use Mockery\MockInterface;
use UnitTester;
use Laminas\Diactoros\ServerRequest;

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

    /**
     * @var CustomerRepositoryInterface|Mockery\LegacyMockInterface|MockInterface
     */
    private $customerRepositoryMock;

    public function _before()
    {
        /** @var CustomerRepositoryInterface|MockInterface $customerFactoryMock */
        $this->customerRepositoryMock = Mockery::mock(CustomerRepositoryInterface::class);
        $customer = $this->tester->createCustomerFixture();
        $this->customerRepositoryMock->shouldReceive('get')
            ->with(Unit::CUSTOMER_EMAIL)
            ->andReturn($customer)
            ->byDefault();

        /** @var CustomerFactory|MockInterface $customerFactoryMock */
        $customerFactoryMock = Mockery::mock(CustomerFactory::class);
        $customerFactoryMock
            ->shouldReceive('createRobinCustomer')
            ->with($customer)
            ->andReturn(new RobinHqCustomerModel(Unit::CUSTOMER_EMAIL));

        $this->dataProvider = new CustomerDataProvider(
            $this->customerRepositoryMock,
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

    public function testNotFoundExceptionIsThrownWhenCustomerDoesNotExist()
    {
        $this->expectException(DataNotFoundException::class);

        $this->customerRepositoryMock->shouldReceive('get')
            ->with(Unit::CUSTOMER_EMAIL)
            ->andThrow(new NoSuchEntityException(__()));

        $request = new ServerRequest();
        $request = $request->withQueryParams(['email' => Unit::CUSTOMER_EMAIL]);

        $this->dataProvider->fetchData($request);
    }
}