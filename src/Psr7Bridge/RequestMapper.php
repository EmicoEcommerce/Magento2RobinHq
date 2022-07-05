<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\Psr7Bridge;

use GuzzleHttp\Psr7\Utils;
use Magento\Framework\HTTP\PhpEnvironment\Request as MagentoRequest;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\ServerRequest as PsrRequest;
use Laminas\Diactoros\Uri;

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
            ->withBody(Utils::streamFor($magentoRequest->getContent()))
            ->withQueryParams($magentoRequest->getQuery()->toArray());

        return $this->mapHeaders($magentoRequest, $psrRequest);
    }

    /**
     * @param MagentoRequest $magentoRequest
     * @param ServerRequestInterface $psrRequest
     * @return ServerRequestInterface
     */
    protected function mapHeaders(MagentoRequest $magentoRequest, ServerRequestInterface $psrRequest): ServerRequestInterface
    {
        foreach ($magentoRequest->getHeaders() as $header) {
            $psrRequest = $psrRequest->withAddedHeader($header->getFieldName(), $header->getFieldValue());
        }
        return $psrRequest;
    }
}
