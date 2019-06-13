<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\ListView\Order;

use Magento\Sales\Api\Data\OrderInterface;

class AggregateProvider implements ListViewProviderInterface
{
    /**
     * @var array|OrderListViewProviderInterface[]
     */
    private $providers;

    /**
     * AggregateDetailViewProvider constructor.
     * @param array $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    public function getData(OrderInterface $order): array
    {
        $providerItems = [];
        foreach ($this->providers as $provider) {
            $providerItems[] = $provider->getData($order);
        }
        return array_merge(...$providerItems);
    }
}