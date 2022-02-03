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
     * @var array|null
     */
    private $columns;

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
     * Get columns, key is column name and value is column comment.
     *
     * @return array
     * @throws Exception
     */
    public function getColumns(): array
    {
        if ($this->columns !== null) {
            return $this->columns;
        }
        $this->columns = [];
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
            $columnDef = array_change_key_case($columnDef, CASE_LOWER);
            $this->columns[$columnDef['column_name']] = $columnDef['column_comment'];
        }

        return $this->columns;
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
            $attributeCodes[] = strtolower($option['value']);
        }
        foreach ($this->getColumns() as $columnName => $columnComment) {
            if (in_array(strtolower($columnName), $attributeCodes, true)) {
                continue;
            }
            $result[] = [
                'value' => $columnName,
                'label' => sprintf(
                    '%s [%s]',
                    $columnComment,
                    $columnName
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
