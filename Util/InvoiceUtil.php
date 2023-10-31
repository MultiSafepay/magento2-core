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

namespace MultiSafepay\ConnectCore\Util;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AfterpayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\EinvoicingConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\KlarnaConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\PayafterConfigProvider;

class InvoiceUtil
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * InvoiceUtil constructor.
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param InvoiceRepositoryInterface $invoiceRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        InvoiceRepositoryInterface $invoiceRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->invoiceRepository = $invoiceRepository;
    }

    /**
     * @param string $orderId
     * @return InvoiceInterface[]
     */
    public function getInvoicesByOrderId(string $orderId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('order_id', $orderId)->create();

        return $this->invoiceRepository->getList($searchCriteria)->getItems();
    }

    /**
     * @param string $orderId
     * @return InvoiceInterface|null
     */
    public function getLastCreatedInvoiceByOrderId(string $orderId): ?InvoiceInterface
    {
        if ($invoices = $this->getInvoicesByOrderId($orderId)) {
            return end($invoices);
        }

        return null;
    }

    /**
     * Get the payment methods which are disallowed from sending invoice e-mails
     *
     * @return array
     */
    public function getDisallowedPaymentMethods(): array
    {
        return [
            PayafterConfigProvider::CODE,
            KlarnaConfigProvider::CODE,
            AfterpayConfigProvider::CODE,
            EinvoicingConfigProvider::CODE,
            LegacyUtil::LEGACY_AFTERPAY_CODE,
            LegacyUtil::LEGACY_KLARNA_CODE,
            LegacyUtil::LEGACY_PAYAFTER_CODE,
            LegacyUtil::LEGACY_EINVOICING_CODE
        ];
    }
}
