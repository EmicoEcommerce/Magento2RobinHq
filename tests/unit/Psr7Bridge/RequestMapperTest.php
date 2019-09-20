<?php

namespace Emico\RobinHqTest\Psr7Bridge;

use Codeception\Test\Unit;
use Emico\RobinHq\Psr7Bridge\RequestMapper;
use Magento\Framework\HTTP\PhpEnvironment\Request as MagentoRequest;
use Mockery;
use UnitTester;
use Zend\Http\Headers;
use Zend\Stdlib\Parameters;

class RequestMapperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testMapMagentoRequestToPsrRequest()
    {
        $method = 'POST';
        $uri = 'http://www.test.nl/';
        $body = 'foo bar';
        $queryParams = ['a' => 'b'];
        $serverParams = ['a' => 'b'];
        $headerName = 'Connection';
        $headerValue = 'close';

        // Create fake magento request
        $magentoRequest = Mockery::mock(MagentoRequest::class);
        $magentoRequest->makePartial();
        $magentoRequest->allows([
            'getMethod' => $method,
            'getContent' => $body,
            'getHeaders' => Headers::fromString($headerName . ': ' . $headerValue)
        ]);
        $magentoRequest->setQuery(new Parameters($queryParams));
        $magentoRequest->setServer(new Parameters($serverParams));
        $magentoRequest->setUri($uri);

        // Call mapper
        $requestMapper = new RequestMapper();
        $psrRequest = $requestMapper->mapToPsrRequest($magentoRequest);

        // Assert PSR request matches expectations
        $this->assertEquals($method, $psrRequest->getMethod());
        $this->assertEquals($uri, (string) $psrRequest->getUri());
        $this->assertEquals($body, (string) $psrRequest->getBody());
        $this->assertEquals($queryParams, $psrRequest->getQueryParams());
        $this->assertEquals($serverParams, $psrRequest->getServerParams());
        $this->assertEquals($headerValue, $psrRequest->getHeader($headerName)[0]);
    }
}