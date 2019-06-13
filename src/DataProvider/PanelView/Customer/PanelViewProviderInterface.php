<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\PanelView\Customer;

use Magento\Customer\Api\Data\CustomerInterface;

interface PanelViewProviderInterface
{
    /**
     * @param CustomerInterface $customer
     * @return array
     */
    public function getData(CustomerInterface $customer): array;
}