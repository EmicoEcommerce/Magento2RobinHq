<?php

namespace Emico\RobinHqTest\Model;

use Codeception\Test\Unit;
use Emico\RobinHq\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Mockery;
use UnitTester;

class ConfigTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function configMappingProvider(): array
    {
        return [
            'getApiKey' => ['getApiKey', 'robinhq/api/key', 'foo', 'foo'],
            'getApiSecret' => ['getApiSecret', 'robinhq/api/secret', 'foo', 'foo'],
            'getApiUri' => ['getApiUri', 'robinhq/api/url', 'foo', 'foo'],
            'getApiServerKey' => ['getApiServerKey', 'robinhq/api/server_key', 'foo', 'foo'],
            'getApiServerSecret' => ['getApiServerSecret', 'robinhq/api/server_secret', 'foo', 'foo'],
            'isApiEnabled' => ['isApiEnabled', 'robinhq/api/server_enabled', '1', true],
            'isPostApiEnabled' => ['isPostApiEnabled', 'robinhq/api/post_enabled', '1', true],
            'getCustomerAttributes' => ['getCustomerAttributes', 'robinhq/custom_attributes/customer_attributes', 'foo,bar', ['foo', 'bar']],
            'getProductAttributes' => ['getProductAttributes', 'robinhq/custom_attributes/product_attributes', 'foo,bar', ['foo', 'bar']],
            'getOrderAttributes' => ['getOrderAttributes', 'robinhq/custom_attributes/order_attributes', 'foo,bar', ['foo', 'bar']],
        ];
    }

    /**
     * @param string $method
     * @param string $expectedConfigPath
     * @param string $configValue
     * @param $expectedReturnValue
     * @dataProvider configMappingProvider
     */
    public function testCorrectConfigPathsAreCalled(string $method, string $expectedConfigPath, string $configValue, $expectedReturnValue): void
    {
        $scopeConfigMock = Mockery::mock(ScopeConfigInterface::class);
        $scopeConfigMock
            ->shouldReceive('getValue')
            ->once()
            ->with($expectedConfigPath, ScopeInterface::SCOPE_STORE)
            ->andReturn($configValue);

        $config = new Config($scopeConfigMock);

        $configValue = call_user_func([$config, $method]);

        $this->assertEquals($expectedReturnValue, $configValue);
    }
}