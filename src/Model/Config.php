<?php
declare(strict_types=1);

namespace Emico\RobinHq\Model;

use Emico\RobinHqLib\Config\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */
class Config implements ConfigInterface
{
    private const PATH_API_KEY = 'robinhq/api/key';
    private const PATH_API_SECRET = 'robinhq/api/secret';
    private const PATH_API_URL = 'robinhq/api/url';
    private const PATH_API_SERVER_KEY = 'robinhq/api/server_key';
    private const PATH_API_SERVER_SECRET = 'robinhq/api/server_secret';
    private const PATH_API_SERVER_ENABLED = 'robinhq/api/server_enabled';
    private const PATH_API_POST_ENABLED = 'robinhq/api/post_enabled';
    private const PATH_CUSTOM_ATTRIBUTES_CUSTOMER_ATTRIBUTES = 'robinhq/custom_attributes/customer_attributes';
    private const PATH_CUSTOM_ATTRIBUTES_PRODUCT_ATTRIBUTES = 'robinhq/custom_attributes/product_attributes';
    private const PATH_CUSTOM_ATTRIBUTES_ORDER_ATTRIBUTES = 'robinhq/custom_attributes/order_attributes';

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return (string)$this->config->getValue(self::PATH_API_KEY, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getApiSecret(): string
    {
        return (string)$this->config->getValue(self::PATH_API_SECRET, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getApiUri(): string
    {
        return (string)$this->config->getValue(self::PATH_API_URL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getApiServerKey(): string
    {
        return (string)$this->config->getValue(self::PATH_API_SERVER_KEY, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getApiServerSecret(): string
    {
        return (string)$this->config->getValue(self::PATH_API_SERVER_SECRET, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function isApiEnabled(): bool
    {
        return $this->config->isSetFlag(self::PATH_API_SERVER_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function isPostApiEnabled(): bool
    {
        return $this->config->isSetFlag(self::PATH_API_POST_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return array
     */
    public function getCustomerAttributes(): array
    {
        return $this->getAttributesList(self::PATH_CUSTOM_ATTRIBUTES_CUSTOMER_ATTRIBUTES);
    }

    /**
     * @return array
     */
    public function getProductAttributes(): array
    {
        return $this->getAttributesList(self::PATH_CUSTOM_ATTRIBUTES_PRODUCT_ATTRIBUTES);
    }

    /**
     * @return array
     */
    public function getOrderAttributes(): array
    {
        return $this->getAttributesList(self::PATH_CUSTOM_ATTRIBUTES_ORDER_ATTRIBUTES);
    }

    /**
     * @return array
     */
    private function getAttributesList(string $path): array
    {
        $result = [];
        $attributesString = $this->config->getValue($path, ScopeInterface::SCOPE_STORE);

        if (null !== $attributesString) {
            $attributes = explode(',', $attributesString);
            $result = array_filter($attributes);
        }

        return $result;
    }
}
