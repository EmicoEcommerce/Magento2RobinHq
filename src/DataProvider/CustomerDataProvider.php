<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider;

use Emico\RobinHq\Mapper\CustomerFactory;
use Emico\RobinHqLib\DataProvider\DataProviderInterface;
use Emico\RobinHqLib\DataProvider\Exception\DataNotFoundException;
use Emico\RobinHqLib\DataProvider\Exception\InvalidRequestException;
use Exception;
use JsonSerializable;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Webmozart\Assert\Assert;

class CustomerDataProvider implements DataProviderInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * CustomerDataProvider constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerFactory             $customerFactory
     * @param StoreManagerInterface       $storeManager
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonSerializable
     * @throws DataNotFoundException
     * @throws LocalizedException
     * @throws Exception
     */
    public function fetchData(ServerRequestInterface $request): JsonSerializable
    {
        $queryParams = $request->getQueryParams();
        Assert::keyExists($queryParams, 'email', 'Email address is missing from request data.');

        $email = $queryParams['email'];
        try {
            $customer = $this->customerRepository->get($email);
        } catch (NoSuchEntityException $e) {
            $customer = $this->findCustomerInAllWebsites($email);
        }

        return $this->customerFactory->createRobinCustomer($customer);
    }

    /**
     * @param string $email
     * @return CustomerInterface
     * @throws DataNotFoundException|LocalizedException
     */
    private function findCustomerInAllWebsites(string $email): CustomerInterface
    {
        foreach ($this->storeManager->getWebsites() as $website) {
            try {
                return $this->customerRepository->get($email, $website->getId());
            } catch (NoSuchEntityException $e) {
                continue;
            }
        }
        throw new DataNotFoundException(sprintf('Could not find customer defined by email %s.', $email));
    }
}
