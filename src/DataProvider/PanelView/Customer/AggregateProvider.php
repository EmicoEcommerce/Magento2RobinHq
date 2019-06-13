<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\PanelView\Customer;


use Magento\Customer\Api\Data\CustomerInterface;

class AggregateProvider implements PanelViewProviderInterface
{
    /**
     * @var array|CustomerPanelViewProviderInterface[]
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
     * @param CustomerInterface $customer
     * @return array
     */
    public function getData(CustomerInterface $customer): array
    {
        $providerItems = [];
        foreach ($this->providers as $provider) {
            $providerItems[] = $provider->getData($customer);
        }
        return array_merge(...$providerItems);
    }
}