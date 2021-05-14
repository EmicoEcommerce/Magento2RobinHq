<?php
/**
 * @author Emico B.V.
 * @copyright (c) Emico B.V. 2021
 */

namespace Emico\RobinHq\DataProvider\Helper;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TrackInterface;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Shipping\Model\ResourceModel\Order\Track\Collection;
use Magento\Shipping\Model\Tracking\Result\Status;

class Tracking
{
    /**
     * @var CarrierFactory
     */
    private $carrierFactory;

    /**
     * Tracking constructor.
     * @param CarrierFactory $carrierFactory
     */
    public function __construct(CarrierFactory $carrierFactory)
    {
        $this->carrierFactory = $carrierFactory;
    }

    /**
     * Retrieve all tracking URL's
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getTrackingData(OrderInterface $order): array
    {
        $trackingData = [];

        /** @var Collection $tracksCollection */
        $tracksCollection = $order->getTracksCollection();

        /** @var TrackInterface $track */
        foreach ($tracksCollection->getItems() as $i => $track) {
            $url = $this->getTrackingUrl($track);
            if ($url) {
                $value = sprintf(
                    '<a href="%s">%s</a>',
                    $track->getTrackNumber(),
                    $url
                );
            } else {
                $value = $track->getTrackNumber();
            }

            $trackingData['track_trace' . $i] = $value;
        }

        return $trackingData;
    }

    /**
     * @param TrackInterface $track
     * @return string|null
     */
    protected function getTrackingUrl(TrackInterface $track)
    {
        /** @var CarrierInterface $carrierInstance */
        $carrierInstance = $this->carrierFactory->create($track->getCarrierCode());
        if (!$carrierInstance) {
            return null;
        }

        $trackingInfo = $carrierInstance->getTrackingInfo($track->getTrackNumber());
        if (!$trackingInfo) {
            return null;
        }

        if ($trackingInfo instanceof Status && $trackingInfo->getData('url') !== null) {
            return (string) $trackingInfo->getData('url');
        }

        return null;
    }
}
