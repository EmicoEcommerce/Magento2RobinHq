<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\DetailView;

use Emico\RobinHqLib\Model\Order\DetailsView;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Rma\Api\Data\ItemInterface;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\Data\TrackInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Helper\Eav;
use Magento\Rma\Model\Item;
use Magento\Rma\Model\Rma;
use Magento\Sales\Api\Data\OrderInterface;

class ReturnsProvider implements DetailViewProviderInterface
{
    /** @var RmaRepositoryInterface */
    private $rmaRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var Eav */
    private $rmaEav;

    /** @var array */
    private $attributeOptionValues;

    /**
     * ReturnsProvider constructor.
     * @param RmaRepositoryInterface  $rmaRepository
     * @param SearchCriteriaBuilder   $searchCriteriaBuilder
     * @param Eav $rmaEav
     */
    public function __construct(
        RmaRepositoryInterface $rmaRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Eav $rmaEav
    ) {
        $this->rmaRepository = $rmaRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->rmaEav = $rmaEav;
    }

    /**
     * @param OrderInterface $order
     * @return array
     * @throws \Exception
     */
    public function getItems(OrderInterface $order): array
    {
        $this->searchCriteriaBuilder->addFilter(Rma::ORDER_ID, $order->getEntityId());
        $searchResults = $this->rmaRepository->getList($this->searchCriteriaBuilder->create());
        if ($searchResults->getTotalCount() === 0) {
            return [];
        }

        $returnItems = [];
        foreach ($searchResults->getItems() as $rma) {
            $trackNumbers = $this->getTrackingNumbers($rma);

            /** @var Item $rmaItem */
            foreach ($rma->getItems() as $rmaItem) {
                $itemData = [
                    __('return_reason')->render()  => $this->getReasonLabel($rmaItem),
                    __('return_track_trace')->render() => implode(', ', $trackNumbers),
                    __('artikelnr_(SKU)')->render() => $rmaItem->getData('product_sku'),
                    __('article name')->render() => $rmaItem->getData('product_name'),
                    __('quantity')->render() => $rmaItem->getQtyRequested()
                ];

                $returnItems[] = $itemData;
            }
        }
        $detailView = new DetailsView(DetailsView::DISPLAY_MODE_ROWS, $returnItems);
        $detailView->setCaption(__('Returns'));
        return [$detailView];
    }

    /**
     * @param RmaInterface $rma
     * @return array
     */
    protected function getTrackingNumbers(RmaInterface $rma): array
    {
        return array_map(
            static function (TrackInterface $track) {
                return $track->getTrackNumber();
            },
            $rma->getTrackingNumbers()->getItems()
        );
    }

    /**
     * @param ItemInterface $rmaItem
     * @return string
     */
    protected function getReasonLabel(ItemInterface $rmaItem): string
    {
        if ($this->attributeOptionValues === null) {
            $this->attributeOptionValues = $this->rmaEav->getAttributeOptionStringValues();
        }
        $reason = $rmaItem->getReason();
        return $this->attributeOptionValues[$reason] ?? $reason;
    }
}
