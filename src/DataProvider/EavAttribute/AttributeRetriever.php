<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @author Pieter Zandbergen <p.zandbergen@emico.nl>
 * @copyright (c) Emico B.V. 2017-2021
 */

namespace Emico\RobinHq\DataProvider\EavAttribute;

use Emico\RobinHq\Model\Config\Source\OrderAttributes;
use Exception;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Model\AbstractExtensibleModel;

class AttributeRetriever
{
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var OrderAttributes
     */
    private $orderAttributes;

    /**
     * AttributeRetriever constructor.
     *
     * @param Config          $eavConfig
     * @param OrderAttributes $orderAttributes
     */
    public function __construct(Config $eavConfig, OrderAttributes $orderAttributes)
    {
        $this->eavConfig = $eavConfig;
        $this->orderAttributes = $orderAttributes;
    }

    /**
     * Get attribute label.
     *
     * @param AbstractAttribute $attribute
     * @return string
     * @throws Exception
     */
    private function getLabel(AbstractAttribute $attribute): string
    {
        // Prefer frontend label
        if (($label = $attribute->getDefaultFrontendLabel()) !== null) {
            return $label;
        }

        // For Order attributes, try the column comment
        $attributeCode = $attribute->getAttributeCode();
        if ($attribute->getEntityType()->getEntityTypeCode() === 'order') {
            if (!empty($this->orderAttributes->getColumns()[$attributeCode])) {
                return $this->orderAttributes->getColumns()[$attributeCode];
            }
        }

        // Use attribute code as a last resort
        return $attributeCode;
    }

    /**
     * @param string $entityType
     * @param AbstractExtensibleModel $model
     * @param string $attributeCode
     * @return AttributeValue|null
     */
    public function getAttributeValue(
        string $entityType,
        AbstractExtensibleModel $model,
        string $attributeCode
    ): ?AttributeValue {
        try {
            $attributeConfig = $this->eavConfig->getAttribute($entityType, $attributeCode);

            $label = $this->getLabel($attributeConfig);

            if ($attributeConfig->usesSource() &&
                method_exists($model, 'getAttributeText')) {
                $value = $model->getAttributeText($attributeCode);
            } else {
                $value = $model->getData($attributeCode);
            }

            if ($value === false || $value === null) {
                return null;
            } else  if (is_array($value)) {
                $value = array_filter($value, function($item) { return is_scalar($item); });
                $value = implode(',', $value);
            } else {
                $value = (string) $value;
            }
        } catch (Exception $e) {
            return null;
        }
        return new AttributeValue($label, $value);
    }
}
