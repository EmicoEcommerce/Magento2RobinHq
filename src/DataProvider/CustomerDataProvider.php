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
use JsonSerializable;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Http\Message\ServerRequestInterface;
use Webmozart\Assert\Assert;

class CustomerDataProvider implements DataProviderInterface
{
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
     * @param CustomerFactory $customerFactory
     */
    public function __construct(CustomerRepositoryInterface $customerRepository, CustomerFactory $customerFactory)
    {
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
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
        Assert::keyExists($queryParams, 'email', 'Email address is missing from request data.');

        $email = $queryParams['email'];
        try {
            $customer = $this->customerRepository->get($email);
        } catch (NoSuchEntityException $e) {
            throw new DataNotFoundException(sprintf('Could not find customer defined by email %s.', $email));
        }
        
        return $this->customerFactory->createRobinCustomer($customer);
    }
}