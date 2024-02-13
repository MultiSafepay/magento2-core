<?php

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
    public const SANTANDER_PATH = 'payment/multisafepay_santander/active';

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

        // Disable Santander for all stores
        foreach ($stores as $store) {
            $storeId = $store->getId();
            $isSantanderActive = (bool)$this->config->getValue(self::SANTANDER_PATH, $storeId);

            if ($isSantanderActive) {
                $this->configWriter->save(
                    self::SANTANDER_PATH,
                    0,
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                );
            }
        }

        // Disable Santander for default store view
        $this->configWriter->save(self::SANTANDER_PATH, 0);
    }
}
