<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Gateway\Command;

use Magento\Framework\DataObject;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\Phrase;
use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\CommandInterface;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Util\AmountUtil;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Service\InvoiceService;

abstract class AbstractCommand extends DataObject implements CommandInterface
{
    /**
     * @var CaptureUtil
     */
    protected $captureUtil;

    /**
     * @var AmountUtil
     */
    protected $amountUtil;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var MessageManager
     */
    protected $messageManager;

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    public function __construct(
        CaptureUtil $captureUtil,
        MessageManager $messageManager,
        SdkFactory $sdkFactory,
        AmountUtil $amountUtil,
        InvoiceService $invoiceService,
        array $data = []
    ) {
        parent::__construct($data);
        $this->captureUtil = $captureUtil;
        $this->messageManager = $messageManager;
        $this->sdkFactory = $sdkFactory;
        $this->amountUtil = $amountUtil;
        $this->invoiceService = $invoiceService;
    }

    /**
     * @param array $commandSubject
     * @return bool
     */
    abstract public function execute(array $commandSubject): bool;

    /**
     * @param int $storeId
     * @return TransactionManager
     */
    public function getTransactionManagerByStoreId(int $storeId): TransactionManager
    {
        return $this->sdkFactory->create((int)$storeId)->getTransactionManager();
    }
}
