<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\DetailView;


use Magento\Sales\Api\Data\OrderInterface;

interface DetailViewProviderInterface
{
    /**
     * @param OrderInterface $order
     * @return array
     */
    public function getItems(OrderInterface $order): array;
}