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

namespace MultiSafepay\ConnectCore\Setup;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use MultiSafepay\ConnectCore\Config\Config;

class UpgradeData implements UpgradeDataInterface
{
    public const PAYMENT_METHOD_PATHS = [
        'payment/multisafepay_santander/active',
        'payment/multisafepay_directbanktransfer/active',
        'payment/multisafepay_giropay/active',
        'payment/multisafepay_alipay/active'
    ];

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @param WriterInterface $configWriter
     * @param Config $config
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        WriterInterface $configWriter,
        Config $config,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->configWriter = $configWriter;
        $this->config = $config;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Run whenever module gets upgraded
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $stores = $this->storeRepository->getList();

        foreach (self::PAYMENT_METHOD_PATHS as $path) {
            foreach ($stores as $store) {
                $storeId = $store->getId();
                $isActive = (bool)$this->config->getValue($path, $storeId);

                if ($isActive) {
                    $this->configWriter->save($path, 0, ScopeInterface::SCOPE_STORE, $storeId);
                }
            }

            $this->configWriter->save($path, 0);
        }
    }
}
