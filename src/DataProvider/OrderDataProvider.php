<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider;

use function count;
use Emico\RobinHq\Mapper\OrderFactory;
use Emico\RobinHqLib\DataProvider\DataProviderInterface;
use Emico\RobinHqLib\DataProvider\Exception\DataNotFoundException;
use Emico\RobinHqLib\DataProvider\Exception\InvalidRequestException;
use JsonSerializable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Webmozart\Assert\Assert;

class OrderDataProvider implements DataProviderInterface
{
    /**
     * OrderDataProvider constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
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
        Assert::keyExists($queryParams, 'orderNumber', 'orderNumber is missing from request data.');

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $queryParams['orderNumber'])
            ->create();
        $orderList = $this->orderRepository->getList($searchCriteria)->getItems();
        if (count($orderList) === 0) {
            throw new DataNotFoundException(sprintf('Could not find order with number %s.', $queryParams['orderNumber']));
        }
        $order = current($orderList);
        
        return $this->orderFactory->createRobinOrder($order);
    }
}