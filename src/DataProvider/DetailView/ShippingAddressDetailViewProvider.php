<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\DetailView;

use Emico\RobinHqLib\Model\Order\DetailsView;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

class ShippingAddressDetailViewProvider implements DetailViewProviderInterface
{
    /**
     * @var CountryInformationAcquirerInterface
     */
    private $countryInformationAcquirer;

    /**
     * ShippingAddressDetailViewProvider constructor.
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     */
    public function __construct(CountryInformationAcquirerInterface $countryInformationAcquirer)
    {
        $this->countryInformationAcquirer = $countryInformationAcquirer;
    }

    /**
     * @param OrderInterface $order
     * @return array
     * @throws \Exception
     */
    public function getItems(OrderInterface $order): array
    {
        /** @var Order $order */
        $address = $order->getShippingAddress();
        if ($address === null) {
            return [];
        }

        $countryInfo = $this->countryInformationAcquirer->getCountryInfo($address->getCountryId());

        $data = [
            __('name')->render() => $address->getName(),
            __('address')->render() => implode(' ', $address->getStreet()),
            __('postalcode + city')->render() => $address->getPostcode() . ' ' . $address->getCity(),
            __('country')->render() => $countryInfo->getFullNameLocale(),
        ];

        $detailView = new DetailsView(DetailsView::DISPLAY_MODE_DETAILS, $data);
        $detailView->setCaption(__('shipping address'));
        return [$detailView];
    }
}