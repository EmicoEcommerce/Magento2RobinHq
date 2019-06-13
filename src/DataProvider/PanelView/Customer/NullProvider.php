<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\PanelView\Customer;

use Magento\Customer\Api\Data\CustomerInterface;

class NullProvider implements PanelViewProviderInterface
{
    /**
     * @param CustomerInterface $customer
     * @return array
     * @throws \Exception
     */
    public function getData(CustomerInterface $customer): array
    {
        return [];
    }
}