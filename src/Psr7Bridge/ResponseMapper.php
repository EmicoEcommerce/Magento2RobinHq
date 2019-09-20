<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\Psr7Bridge;

use Magento\Framework\HTTP\PhpEnvironment\Response;
use Psr\Http\Message\ResponseInterface;

class ResponseMapper
{
    /**
     * @param ResponseInterface $psrResponse
     * @param Response $magentoResponse
     * @return void
     */
    public function mapPsrToMagentoResponse(ResponseInterface $psrResponse, Response $magentoResponse): void
    {
        $magentoResponse->setBody($psrResponse->getBody());
        $magentoResponse->setCustomStatusCode($psrResponse->getStatusCode());
        foreach ($psrResponse->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $magentoResponse->getHeaders()->addHeaderLine($name, $value);
            }
        }
    }
}