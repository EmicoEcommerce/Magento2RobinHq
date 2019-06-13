<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\ListView\Order;

use Magento\Sales\Api\Data\OrderInterface;

interface ListViewProviderInterface
{
    /**
     * @param OrderInterface $order
     * @return array
     */
    public function getData(OrderInterface $order): array;
}