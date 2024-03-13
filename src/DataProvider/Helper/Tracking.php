<?php
/**
 * @author Emico B.V.
 * @copyright (c) Emico B.V. 2021
 */

namespace Emico\RobinHq\DataProvider\Helper;

use Magento\Framework\Escaper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TrackInterface;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Shipping\Model\ResourceModel\Order\Track\Collection;
use Magento\Shipping\Model\Tracking\Result\Status;

class Tracking
{
    /**
     * Tracking constructor.
     * @param CarrierFactory $carrierFactory
     * @param Escaper        $escaper
     */
    public function __construct(private CarrierFactory $carrierFactory, private Escaper $escaper)
    {
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
        foreach ($tracksCollection->getItems() as $track) {
            $url = $this->getTrackingUrl($track);
            $trackData = array_filter(array_merge([
                'title' => $track->getTitle(),
                'track_number' => $track->getTrackNumber(),
            ], $url ? [
                '' => '<div class="panel_button"><div><a href="'
                    . $this->escaper->escapeHtmlAttr($url)
                    . '" target="_blank">Details</a></div></div>'
            ] : []));
            if (empty($trackData)) {
                continue;
            }
            $trackingData[] = $trackData;
        }

        return $trackingData;
    }

    /**
     * @param TrackInterface $track
     * @return string|null
     */
    protected function getTrackingUrl(TrackInterface $track)
    {
        $carrierInstance = $this->carrierFactory->create($track->getCarrierCode());
        if (!$carrierInstance || !($carrierInstance instanceof AbstractCarrierOnline)) {
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
