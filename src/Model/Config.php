<?php
declare(strict_types = 1);

namespace Emico\RobinHq\Model;

use Emico\RobinHqLib\Config\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @author        Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */
class Config implements ConfigInterface
{
    public const XML_PATH_API_KEY = 'robinhq/api/key';
    public const XML_PATH_API_SECRET = 'robinhq/api/secret';
    public const XML_PATH_API_URL = 'robinhq/api/url';
    public const XML_PATH_API_SERVER_KEY = 'robinhq/api/server_key';
    public const XML_PATH_API_SERVER_SECRET = 'robinhq/api/server_secret';
    public const XML_PATH_API_SERVER_ENABLED = 'robinhq/api/server_enabled';
    public const XML_PATH_API_POST_ENABLED = 'robinhq/api/post_enabled';
    public const XML_PATH_CUSTOM_ATTRIBUTES_CUSTOMER_ATTRIBUTES = 'robinhq/custom_attributes/customer_attributes';
    public const XML_PATH_CUSTOM_ATTRIBUTES_PRODUCT_ATTRIBUTES = 'robinhq/custom_attributes/product_attributes';
    public const XML_PATH_CUSTOM_ATTRIBUTES_ORDER_ATTRIBUTES = 'robinhq/custom_attributes/order_attributes';

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $config
     */
    public function __construct(private ScopeConfigInterface $config)
    {
    }

    /**
     * @param string $path
     * @param string $scopeType
     *
     * @return int|string|null
     */
    protected function getConfigValue(string $path, string $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->config->getValue($path, $scopeType);
    }

    /**
     * @param string $path
     * @param string $scopeType
     *
     * @return bool
     */
    protected function isConfigFlagSet(string $path, string $scopeType = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->config->isSetFlag($path, $scopeType);
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return (string)$this->getConfigValue(self::XML_PATH_API_KEY);
    }

    /**
     * @return string
     */
    public function getApiSecret(): string
    {
        return (string)$this->getConfigValue(self::XML_PATH_API_SECRET);
    }

    /**
     * @return string
     */
    public function getApiUri(): string
    {
        return (string)$this->getConfigValue(self::XML_PATH_API_URL);
    }

    /**
     * @return string
     */
    public function getApiServerKey(): string
    {
        return (string)$this->getConfigValue(self::XML_PATH_API_SERVER_KEY);
    }

    /**
     * @return string
     */
    public function getApiServerSecret(): string
    {
        return (string)$this->getConfigValue(self::XML_PATH_API_SERVER_SECRET);
    }

    /**
     * @return bool
     */
    public function isApiEnabled(): bool
    {
        return $this->isConfigFlagSet(self::XML_PATH_API_SERVER_ENABLED);
    }

    /**
     * @return bool
     */
    public function isPostApiEnabled(): bool
    {
        return $this->isConfigFlagSet(self::XML_PATH_API_POST_ENABLED);
    }

    /**
     * @param string $configPath
     *
     * @return array
     */
    protected function getCustomAttributes(string $configPath): array
    {
        $value = $this->getConfigValue($configPath);
        if (empty($value)) {
            return [];
        }

        return array_filter(explode(',', $value));
    }

    /**
     * @return array
     */
    public function getCustomerAttributes(): array
    {
        return $this->getCustomAttributes(self::XML_PATH_CUSTOM_ATTRIBUTES_CUSTOMER_ATTRIBUTES);
    }

    /**
     * @return array
     */
    public function getProductAttributes(): array
    {
        return $this->getCustomAttributes(self::XML_PATH_CUSTOM_ATTRIBUTES_PRODUCT_ATTRIBUTES);
    }

    /**
     * @return array
     */
    public function getOrderAttributes(): array
    {
        return $this->getCustomAttributes(self::XML_PATH_CUSTOM_ATTRIBUTES_ORDER_ATTRIBUTES);
    }
}
