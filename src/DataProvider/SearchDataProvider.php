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
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var OrderFactory
     */
    private OrderFactory $orderFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var CustomerFactory
     */
    private CustomerFactory $customerFactory;

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

        $filters = [$emailFilter];

        // add Phone Number search if applicable
        $phoneNumberSearchFilter = $this->createPhoneNumberSearchFilter($searchTerm);
        if ($phoneNumberSearchFilter instanceof Filter) {
            $filters[] = $phoneNumberSearchFilter;
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters($filters)
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

    /**
     * This method creates a Filter for phone numbers
     * If it seems a Dutch Phone number, the term will be parsed in 3 forms and used
     * as a literal search. Otherwise it will do a like search if the searchterm is a
     * numeric value.
     *
     * @param mixed $searchTerm
     * @return Filter|null
     */
    protected function createPhoneNumberSearchFilter($searchTerm): ?Filter
    {
        // Does it seem a Dutch PhoneNumber? When yes, return a literal search filter
        if ($this->seemsDutchNumber($searchTerm) === true) {
            $dutchPhoneNumbers = $this->createDutchPhoneNumbers($searchTerm);
            return (new Filter())
                ->setField('billing_telephone')
                ->setValue(implode(',', $dutchPhoneNumbers))
                ->setConditionType('in');
        }

        // Is it a phone number at all?
        $parsedPhoneNumber = $this->parsePhoneNumber($searchTerm);
        if ($parsedPhoneNumber !== false) {
            // It seems to be a phone number, search as such
            return (new Filter())
                ->setField('billing_telephone')
                ->setValue('%' . $parsedPhoneNumber)
                ->setConditionType('like');
        }

        return null;
    }

    /**
     * This method validates if the searchterm is a numeric value.
     * If so, it returns the last 9 digits. Otherwise, it returns False
     *
     * @param mixed $searchTerm
     * @return integer|false
     */
    protected function parsePhoneNumber($searchTerm)
    {
        // it doesn't seem to be a Dutch number, is it numeric?
        if (is_numeric($searchTerm) === true) {
            // yes, so again return the last 9 digits
            return (int) substr($searchTerm, -9);
        }

        // in all other cases it isn't a phone number for sure
        return false;
    }

    /**
     * This method performs three tests to validate if the value seems to be a Dutch phone number
     * The first test is international format with + symbol. The second test is international format
     * and the third test is local format with preceding 0.
     *
     * @param mixed $searchTerm
     * @return boolean
     */
    protected function seemsDutchNumber($searchTerm): bool
    {
        if ((bool) preg_match('/^\+31[0-9]{9}$/', $searchTerm) !== false) {
            return true;
        }

        if ((bool) preg_match('/^0031[0-9]{9}$/', $searchTerm) !== false) {
            return true;
        }

        if ((bool) preg_match('/^0[0-9]{9}$/', $searchTerm) !== false) {
            return true;
        }

        return false;
    }

    /**
     * This method returns an array with formatted phone numbers
     *
     * @param mixed $phoneNumber
     * @return string[]
     */
    protected function createDutchPhoneNumbers($phoneNumber): array
    {
        $phoneNumber = (int) substr($phoneNumber, -9);
        return [
            '+31' . $phoneNumber,
            '0031' . $phoneNumber,
            '0' . $phoneNumber
        ];
    }
}
