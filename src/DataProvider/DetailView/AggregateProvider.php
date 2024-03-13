<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\DetailView;


use Magento\Sales\Api\Data\OrderInterface;

class AggregateProvider implements DetailViewProviderInterface
{
    /**
     * @var array|DetailViewProviderInterface[]
     */
    private array $providers;

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
    public function getItems(OrderInterface $order): array
    {
        $providerItems = [];
        foreach ($this->providers as $provider) {
            $providerItems[] = $provider->getItems($order);
        }
        return array_merge(...$providerItems);
    }
}