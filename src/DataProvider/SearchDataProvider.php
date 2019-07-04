<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider;

use Emico\RobinHq\Mapper\CustomerFactory;
use Emico\RobinHq\Mapper\OrderFactory;
use Emico\RobinHqLib\DataProvider\DataProviderInterface;
use Emico\RobinHqLib\DataProvider\Exception\DataNotFoundException;
use Emico\RobinHqLib\Model\Collection;
use Emico\RobinHqLib\Model\SearchResult;
use JsonSerializable;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Magento\Framework\Api\Filter;
use Webmozart\Assert\Assert;

class SearchDataProvider implements DataProviderInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * OrderDataProvider constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerFactory $customerFactory
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerFactory $customerFactory,
        OrderFactory $orderFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonSerializable
     * @throws DataNotFoundException
     * @throws LocalizedException
     */
    public function fetchData(ServerRequestInterface $request): JsonSerializable
    {
        $queryParams = $request->getQueryParams();
        Assert::keyExists($queryParams, 'searchTerm', 'searchTerm is missing from request data.');
        $searchTerm = $queryParams['searchTerm'];

        return new SearchResult(
            $this->getCustomers($searchTerm),
            $this->getOrders($searchTerm)
        );
    }

    /**
     * @param string $searchTerm
     * @return Collection
     * @throws LocalizedException
     */
    protected function getCustomers(string $searchTerm): Collection
    {
        $customerCollection = new Collection([]);

        $emailFilter = (new Filter())
            ->setField(CustomerInterface::EMAIL)
            ->setValue($searchTerm . '%')
            ->setConditionType('like');

        $telephoneFilter = (new Filter())
            ->setField('billing_telephone')
            ->setValue($searchTerm . '%')
            ->setConditionType('like');

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters([$emailFilter, $telephoneFilter])
            ->setPageSize(10)
            ->create();

        $customers = $this->customerRepository
            ->getList($searchCriteria)
            ->getItems();

        foreach ($customers as $customer) {
            $customerCollection->addElement($this->customerFactory->createRobinCustomer($customer));
        }

        return $customerCollection;
    }

    /**
     * @param string $searchTerm
     * @return Collection
     * @throws LocalizedException
     * @throws \Exception
     */
    protected function getOrders(string $searchTerm): Collection
    {
        $customerCollection = new Collection([]);

        $emailFilter = (new Filter())
            ->setField(OrderInterface::CUSTOMER_EMAIL)
            ->setValue($searchTerm . '%')
            ->setConditionType('like');

        $idFilter = (new Filter())
            ->setField(OrderInterface::INCREMENT_ID)
            ->setValue($searchTerm . '%')
            ->setConditionType('like');

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters([$emailFilter, $idFilter])
            ->setPageSize(10)
            ->create();

        $orders = $this->orderRepository
            ->getList($searchCriteria)
            ->getItems();

        foreach ($orders as $order) {
            $customerCollection->addElement($this->orderFactory->createRobinOrder($order));
        }

        return $customerCollection;
    }
}