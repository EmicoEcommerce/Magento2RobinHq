<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\DetailView;


use Emico\RobinHqLib\Model\Order\DetailsView;
use Magento\Sales\Api\Data\OrderInterface;

class NullProvider implements DetailViewProviderInterface
{
    /**
     * @param OrderInterface $order
     * @return array
     * @throws \Exception
     */
    public function getItems(OrderInterface $order): array
    {
        return [];
    }
}