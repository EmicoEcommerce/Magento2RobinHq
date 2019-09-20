<?php

namespace Emico\RobinHqTest\Psr7Bridge;

use Codeception\Test\Unit;
use Emico\RobinHq\Psr7Bridge\ResponseMapper;
use UnitTester;
use Zend\Diactoros\Response;

class ResponseMapperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testMapMagentoRequestToPsrRequest()
    {
        $statusCode = 201;
        $body = 'foo bar';
        $headers = ['Connection' => 'close'];

        // Create responses
        $psrResponse = new Response('php://memory', $statusCode, $headers);
        $psrResponse->getBody()->write($body);

        $magentoResponse = new \Magento\Framework\HTTP\PhpEnvironment\Response();

        // Call mapper
        $responseMapper = new ResponseMapper();
        $responseMapper->mapPsrToMagentoResponse($psrResponse, $magentoResponse);

        // Assert PSR request matches expectations
        $this->assertEquals($body, $magentoResponse->getBody());
        $this->assertEquals($statusCode, $magentoResponse->getStatusCode());
        $this->assertEquals($headers, $magentoResponse->getHeaders()->toArray());
    }
}