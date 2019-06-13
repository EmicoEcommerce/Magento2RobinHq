<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\Service;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address\AbstractAddress;

class CustomerService
{
    /**
     * @param CustomerInterface $customer
     * @return AddressInterface|null
     */
    public function getDefaultAddress(CustomerInterface $customer): ?AddressInterface
    {
        $customerAddresses = $customer->getAddresses();
        if (count($customerAddresses) === 0) {
            return null;
        }

        $defaultAddress = null;
        foreach ($customerAddresses as $address) {
            if ($address->isDefaultBilling()) {
                $defaultAddress = $address;
            }
            if ($defaultAddress === null && $address->isDefaultShipping()) {
                $defaultAddress = $address;
            }
        }

        return $defaultAddress ?? $customerAddresses[0];
    }

    /**
     * @param CustomerInterface $customer
     * @return AddressInterface|null
     */
    public function getShippingAddress(CustomerInterface $customer): ?AbstractAddress
    {
        foreach ($customer->getAddresses() as $address) {
            if ($address->isDefaultShipping()) {
                return $address;
            }
        }
        return null;
    }
}