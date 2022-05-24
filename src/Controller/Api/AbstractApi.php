<?php
namespace Emico\RobinHq\Controller\Api;

use Emico\RobinHq\DataProvider\CustomerDataProvider;
use Emico\RobinHq\Psr7Bridge\RequestMapper;
use Emico\RobinHq\Psr7Bridge\ResponseMapper;
use Emico\RobinHqLib\DataProvider\DataProviderInterface;
use Emico\RobinHqLib\Server\RestApiServer;
use InvalidArgumentException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\ServerRequestFactory;
use Magento\Framework\App\Response\Http as ResponseHttp;

abstract class AbstractApi extends Action
{
    /**
     * @var RestApiServer
     */
    private $restApiServer;
    /**
     * @var ResponseHttp
     */
    private $response;
    /**
     * @var DataProviderInterface
     */
    private $dataProvider;

    /**
     * @var RequestMapper
     */
    private $requestMapper;
    /**
     * @var ResponseMapper
     */
    private $responseMapper;

    /**
     * Customer constructor.
     * @param Context $context
     * @param RestApiServer $restApiServer
     * @param DataProviderInterface $dataProvider
     * @param ResponseHttp $response
     * @param RequestMapper $requestMapper
     * @param ResponseMapper $responseMapper
     */
    public function __construct(
        Context $context,
        RestApiServer $restApiServer,
        DataProviderInterface $dataProvider,
        ResponseHttp $response,
        RequestMapper $requestMapper,
        ResponseMapper $responseMapper
    ) {
        parent::__construct($context);
        $this->restApiServer = $restApiServer;
        $this->response = $response;
        $this->dataProvider = $dataProvider;
        $this->requestMapper = $requestMapper;
        $this->responseMapper = $responseMapper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $request = $this->getRequest();
        if (!$request instanceof Request) {
            throw new InvalidArgumentException('Can only dispatch PhpEnvironment requests');
        }

        $request = $this->requestMapper->mapToPsrRequest($request);
        $response = $this->restApiServer->handleRequest($request, $this->dataProvider);
        $this->responseMapper->mapPsrToMagentoResponse($response, $this->response);
    }
}