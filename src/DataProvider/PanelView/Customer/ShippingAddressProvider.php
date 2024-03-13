<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\PanelView\Customer;


use Emico\RobinHq\Service\CustomerService;
use Magento\Customer\Api\Data\CustomerInterface;

class ShippingAddressProvider implements PanelViewProviderInterface
{
    /**
     * CustomerAddressProvider constructor.
     * @param CustomerService $customerService
     */
    public function __construct(private CustomerService $customerService)
    {
    }

    /**
     * @param CustomerInterface $customer
     * @return array
     */
    public function getData(CustomerInterface $customer): array
    {
        $address = $this->customerService->getShippingAddress($customer);
        if ($address === null) {
            return [];
        }

        $street = $address->getStreet();
        if (\is_array($street)) {
            $street = implode("\n", $street);
        }

        return [
            'street' => $street,
            'postalCode' => $address->getPostcode(),
            'city' => $address->getCity()
        ];
    }
}