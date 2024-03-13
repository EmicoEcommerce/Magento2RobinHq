<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider;

use Emico\RobinHq\Mapper\OrderFactory;
use Emico\RobinHqLib\DataProvider\DataProviderInterface;
use Emico\RobinHqLib\DataProvider\Exception\DataNotFoundException;
use Emico\RobinHqLib\DataProvider\Exception\InvalidRequestException;
use Emico\RobinHqLib\Model\Collection;
use JsonSerializable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Webmozart\Assert\Assert;

class CustomerOrdersDataProvider implements DataProviderInterface
{
    /**
     * OrderDataProvider constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private SortOrderBuilder $sortOrderBuilder,
        private OrderFactory $orderFactory
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonSerializable
     * @throws DataNotFoundException
     * @throws InvalidRequestException
     * @throws LocalizedException
     */
    public function fetchData(ServerRequestInterface $request): JsonSerializable
    {
        $queryParams = $request->getQueryParams();
        Assert::keyExists($queryParams, 'email', 'email is missing from request data.');

        $sortOrder = $this->sortOrderBuilder
            ->setField(OrderInterface::CREATED_AT)
            ->setDescendingDirection()
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OrderInterface::CUSTOMER_EMAIL, $queryParams['email'])
            ->addSortOrder($sortOrder)
            ->create();

        $orders = $this->orderRepository->getList($searchCriteria)->getItems();

        $robinCollection = new Collection([], 'orders');
        foreach ($orders as $order) {
            $robinCollection->addElement(
                $this->orderFactory->createRobinOrder($order)
            );
        }

        return $robinCollection;
    }
}