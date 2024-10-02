<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Gateway\Validator;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Config\Config;
use Magento\Quote\Model\Quote;

class CustomerGroupValidator
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * CustomerGroupValidator constructor.
     *
     * @param Session $customerSession
     */
    public function __construct(Session $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    /**
     * @param Quote $quote
     * @param Config $config
     * @param string $methodCode
     * @return bool
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(Quote $quote, Config $config, string $methodCode): bool
    {
        $storeId = $quote->getStoreId();

        if ((int)$config->getValue('allow_specific_customer_group', $storeId) === 1) {
            $availableCustomerGroups = explode(
                ',',
                (string)$config->getValue('allowed_customer_group', $storeId)
            );

            return !in_array(
                ($this->customerSession->isLoggedIn() && $this->customerSession->getCustomer()->getId())
                    ? (int)$this->customerSession->getCustomer()->getGroupId() : (int)$quote->getCustomerGroupId(),
                $availableCustomerGroups
            );
        }

        return false;
    }
}
