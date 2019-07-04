<?php
namespace Emico\RobinHq\Controller\Api;

use Emico\RobinHq\DataProvider\CustomerDataProvider;
use Emico\RobinHq\Psr7Bridge\RequestMapper;
use Emico\RobinHqLib\DataProvider\DataProviderInterface;
use Emico\RobinHqLib\Server\RestApiServer;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\ServerRequestFactory;
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
     * Customer constructor.
     * @param Context $context
     * @param RestApiServer $restApiServer
     * @param DataProviderInterface $dataProvider
     * @param ResponseHttp $response
     */
    public function __construct(
        Context $context,
        RestApiServer $restApiServer,
        DataProviderInterface $dataProvider,
        ResponseHttp $response
    ) {
        parent::__construct($context);
        $this->restApiServer = $restApiServer;
        $this->response = $response;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $requestMapper = new RequestMapper();
        //$request = ServerRequestFactory::fromGlobals();


        //$request = $this->getRequest();

        $request = $requestMapper->mapToPsrRequest($this->getRequest());
        $response = $this->restApiServer->handleRequest($request, $this->dataProvider);
        $this->mapPsrResponseToMagentoResponse($response);
    }

    /**
     * @param ResponseInterface $response
     */
    protected function mapPsrResponseToMagentoResponse(ResponseInterface $response): void
    {
        $this->response->setBody($response->getBody());
        $this->response->setCustomStatusCode($response->getStatusCode());
    }
}