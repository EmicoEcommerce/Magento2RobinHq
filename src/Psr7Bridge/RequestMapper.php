<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\Psr7Bridge;

use function GuzzleHttp\Psr7\stream_for;
use Magento\Framework\HTTP\PhpEnvironment\Request as MagentoRequest;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest as PsrRequest;
use Zend\Diactoros\Uri;

class RequestMapper
{
    /**
     * @param MagentoRequest $magentoRequest
     * @return ServerRequestInterface
     */
    public function mapToPsrRequest(MagentoRequest $magentoRequest): ServerRequestInterface
    {
        $psrRequest = (new PsrRequest($magentoRequest->getServer()->toArray()))
            ->withMethod($magentoRequest->getMethod())
            ->withUri(new Uri($magentoRequest->getUri()->__toString()))
            ->withBody(stream_for($magentoRequest->getContent()))
            ->withQueryParams($magentoRequest->getQuery()->toArray());

        $this->mapHeaders($magentoRequest, $psrRequest);
        return $psrRequest;
    }

    /**
     * @param MagentoRequest $magentoRequest
     * @param ServerRequestInterface $psrRequest
     */
    protected function mapHeaders(MagentoRequest $magentoRequest, ServerRequestInterface $psrRequest)
    {
        foreach ($magentoRequest->getHeaders() as $header) {
            $psrRequest = $psrRequest->withAddedHeader($header->getFieldName(), $header->getFieldValue());
        }
    }
}