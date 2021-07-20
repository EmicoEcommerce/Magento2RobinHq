<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\DetailView;

use Emico\RobinHq\DataProvider\Helper\Tracking;
use Emico\RobinHqLib\Model\Order\DetailsView;
use Magento\Sales\Api\Data\OrderInterface;

class TrackTraceProvider implements DetailViewProviderInterface
{
    /**
     * @var Tracking
     */
    private $tracking;

    /**
     * TrackTraceProvider constructor.
     * @param Tracking $tracking
     */
    public function __construct(Tracking $tracking)
    {
        $this->tracking = $tracking;
    }

    /**
     * @param OrderInterface $order
     * @return array
     * @throws \Exception
     */
    public function getItems(OrderInterface $order): array
    {
        $trackingData = $this->tracking->getTrackingData($order);
        if (empty($trackingData)) {
            return [];
        }

        $detailView = new DetailsView(DetailsView::DISPLAY_MODE_ROWS, $trackingData);
        $detailView->setCaption(__('Packages'));
        return [$detailView];
    }
}
