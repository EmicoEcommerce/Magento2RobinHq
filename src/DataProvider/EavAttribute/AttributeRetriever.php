<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\EavAttribute;

use Magento\Eav\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractExtensibleModel;

class AttributeRetriever
{
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * AttributeRetriever constructor.
     * @param Config $eavConfig
     */
    public function __construct(Config $eavConfig)
    {
        $this->eavConfig = $eavConfig;
    }

    /**
     * @param string $entityType
     * @param AbstractExtensibleModel $model
     * @param string $attributeCode
     * @return mixed
     */
    public function getAttributeValue(string $entityType, AbstractExtensibleModel $model, string $attributeCode): ?AttributeValue
    {
        try {
            $attributeConfig = $this->eavConfig->getAttribute($entityType, $attributeCode);

            $label = $attributeConfig->getDefaultFrontendLabel();

            if ($attributeConfig->usesSource() &&
                method_exists($model, 'getAttributeText')) {
                $value = $model->getAttributeText($attributeCode);
            } else {
                $value = $model->getData($attributeCode);
            }

            if ($value === false) {
                return null;
            } else  if (is_array($value)) {
                $value = array_filter($value, function($item) { return is_scalar($item); });
                $value = implode(',', $value);
            } else {
                $value = (string) $value;
            }
        } catch (LocalizedException $e) {
            return null;
        }
        return new AttributeValue($label, $value);
    }
}
