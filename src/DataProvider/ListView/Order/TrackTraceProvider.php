<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\ListView\Order;


use Emico\RobinHq\DataProvider\Helper\Tracking;
use Magento\Sales\Api\Data\OrderInterface;

class TrackTraceProvider implements ListViewProviderInterface
{
    /**
     * @var Tracking
     */
    private $trackingHelper;

    /**
     * TrackTraceProvider constructor.
     * @param Tracking $trackingHelper
     */
    public function __construct(Tracking $trackingHelper)
    {
        $this->trackingHelper = $trackingHelper;
    }

    /**
     * @param OrderInterface $order
     * @return array
     * @throws \Exception
     */
    public function getData(OrderInterface $order): array
    {
        return $this->trackingHelper->getTrackingData($order);
    }
}