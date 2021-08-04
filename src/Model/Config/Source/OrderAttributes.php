<?php
/**
 * @author Pieter Zandbergen <p.zandbergen@emico.nl>
 * @copyright (c) Emico B.V. 2021
 */
declare(strict_types = 1);

namespace Emico\RobinHq\Model\Config\Source;

use Exception;
use Magento\Eav\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class OrderAttributes extends Attributes implements OptionSourceInterface
{
    /**
     * @var OrderResource
     */
    private $orderResource;

    /**
     * OrderAttributes constructor.
     *
     * @param Config        $eavConfig
     * @param OrderResource $orderResource
     */
    public function __construct(Config $eavConfig, OrderResource $orderResource)
    {
        $this->orderResource = $orderResource;
        parent::__construct($eavConfig, Order::ENTITY);
    }

    /**
     * Add flat attributes.
     *
     * @inheritDoc
     * @return array
     * @throws Exception
     */
    public function toOptionArray(): array
    {
        $result = parent::toOptionArray();
        $attributeCodes = [];
        foreach ($result as $option) {
            $attributeCodes[] = $option['value'];
        }
        $connection = $this->orderResource->getConnection();
        $select = $connection->select()
            ->from('information_schema.columns', [
                'column_name',
                'column_comment',
            ])
            ->where('table_schema = DATABASE()')
            ->where('table_name = ?', $this->orderResource->getMainTable());
        $queryResult = $connection->query($select);
        foreach ($queryResult->fetchAll() as $columnDef) {
            if (in_array($columnDef['column_name'], $attributeCodes, true)) {
                continue;
            }
            $result[] = [
                'value' => $columnDef['column_name'],
                'label' => sprintf(
                    '%s [%s]',
                    $columnDef['column_comment'],
                    $columnDef['column_name']
                ),
            ];
        }

        // Sort
        usort($result, function(array $a, array $b) {
            return strnatcasecmp($a['label'], $b['label']);
        });

        return $result;
    }
}
