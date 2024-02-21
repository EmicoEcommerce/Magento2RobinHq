<?php


use Codeception\Test\Unit;
use Emico\RobinHq\Controller\Api\AbstractApi;
use Emico\RobinHq\Controller\Api\Customer;
use Emico\RobinHq\Controller\Api\CustomerOrders;
use Emico\RobinHq\Controller\Api\Lifetime;
use Emico\RobinHq\Controller\Api\Order;
use Emico\RobinHq\Controller\Api\Search;
use Emico\RobinHqLib\Server\RestApiServer;
use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ApiControllerTest extends Unit
{
    public function endpointProvider(): array
    {
        return [
            [Customer::class],
            [CustomerOrders::class],
            [Lifetime::class],
            [Order::class],
            [Search::class],
        ];
    }

    /**
     * @param string $actionClass
     * @dataProvider endpointProvider
     */
    public function testApiEndpointsAreDispatchedCorrectly(string $actionClass)
    {
        $objectManager = new ObjectManager($this);

        $requestMock = Mockery::mock(Request::class);
        $responseMock = Mockery::mock(Response::class);
        $restApiServer = Mockery::mock(RestApiServer::class);
        $restApiServer
            ->shouldReceive('handleRequest')
            ->once();

        $actionContextMock = Mockery::mock(
            ActionContext::class,
            [
                'getRequest' => $requestMock,
                'getResponse' => $responseMock
            ]
        );
        $actionContextMock->makePartial();

        /** @var AbstractApi $controller */
        $controller = $objectManager->getObject($actionClass, [
            'context' => $actionContextMock,
            'restApiServer' => $restApiServer
        ]);

        $controller->execute();
        Mockery::close();
    }

    public function testExceptionIsThrownOnInvalidRequest()
    {
        $this->expectException(InvalidArgumentException::class);

        $objectManager = new ObjectManager($this);

        $actionContextMock = Mockery::mock(
            ActionContext::class,
            [
                'getRequest' => Mockery::mock(\Laminas\Http\Request::class),
            ]
        );
        $actionContextMock->makePartial();

        $controller = $objectManager->getObject(Customer::class, [
            'context' => $actionContextMock
        ]);

        $controller->execute();
    }
}