<?php

namespace Emico\RobinHq\Model\Config\Source;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

class Attributes implements OptionSourceInterface
{
    /**
     * @var Config
     */
    private $eavConfig;
    /**
     * @var string
     */
    private $entityType;

    /**
     * CustomerAttributes constructor.
     * @param Config $eavConfig
     * @param string $entityType
     */
    public function __construct(Config $eavConfig, $entityType = Customer::ENTITY)
    {
        $this->eavConfig = $eavConfig;
        $this->entityType = $entityType;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $result = [];
        foreach ($this->eavConfig->getEntityAttributes($this->entityType) as $attribute) {
            $result[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => sprintf('%s [%s]', $attribute->getDefaultFrontendLabel(), $attribute->getAttributeCode()),
            ];
        }

        usort($result, function(array $a, array $b) {
            return strnatcasecmp($a['label'], $b['label']);
        });

        return $result;
    }
}