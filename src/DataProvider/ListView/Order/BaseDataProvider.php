<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\ListView\Order;


use Magento\Sales\Api\Data\OrderInterface;

class BaseDataProvider implements ListViewProviderInterface
{
    /**
     * @param OrderInterface $order
     * @return array
     * @throws \Exception
     */
    public function getData(OrderInterface $order): array
    {
        return [
            'order_number' => $order->getIncrementId(),
            'date' => (new \DateTimeImmutable($order->getCreatedAt()))->format('d-m-Y'),
            'status' => $order->getStatus()
        ];
    }
}