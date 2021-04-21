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

namespace MultiSafepay\ConnectCore\Util;

use Magento\AdminNotification\Model\InboxFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Framework\FlagManager;

class NotificationUtil
{
    private const MULTISAFEPAY_LAST_RELEASE_NOTIFICATION_CONFIG_PATH = 'multisafepay/support/last_release_notification';

    /**
     * @var VersionUtil
     */
    private $versionUtil;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var InboxFactory
     */
    private $inboxFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * NotificationUtil constructor.
     *
     * @param VersionUtil $versionUtil
     * @param Escaper $escaper
     * @param InboxFactory $inboxFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param FlagManager $flagManager
     */
    public function __construct(
        VersionUtil $versionUtil,
        Escaper $escaper,
        InboxFactory $inboxFactory,
        ScopeConfigInterface $scopeConfig,
        FlagManager $flagManager
    ) {
        $this->versionUtil = $versionUtil;
        $this->escaper = $escaper;
        $this->inboxFactory = $inboxFactory;
        $this->scopeConfig = $scopeConfig;
        $this->flagManager = $flagManager;
    }

    /**
     * @return void
     */
    public function addNewReleaseNotification(): void
    {
        $lastNotifiedVersion = (string)$this->flagManager
            ->getFlagData(self::MULTISAFEPAY_LAST_RELEASE_NOTIFICATION_CONFIG_PATH);
        $newReleaseData = $this->versionUtil->getNewVersionsDataIfExist();

        if ($newReleaseData && version_compare($newReleaseData['version'], $lastNotifiedVersion)) {
            $inbox = $this->inboxFactory->create();
            $inbox->addNotice(
                $this->getReleaseNotificationTitle($newReleaseData['version']),
                $this->getReleaseNotificationDescription($newReleaseData),
                $this->escaper->escapeUrl($newReleaseData['url'])
            );

            $this->flagManager->saveFlag(
                self::MULTISAFEPAY_LAST_RELEASE_NOTIFICATION_CONFIG_PATH,
                $newReleaseData['version']
            );
        }
    }

    /**
     * @param string $newVersion
     * @return string
     */
    private function getReleaseNotificationTitle(string $newVersion): string
    {
        return __('MultiSafepay: New version of plugin %1 was released', $newVersion)->render();
    }

    /**
     * @param array $newReleaseData
     * @return string
     */
    private function getReleaseNotificationDescription(array $newReleaseData): string
    {
        return __(
            'Please, upgrade to the last version: %1. Here is list of changes: %2.'
            . 'If you have any questions regarding the plugin, feel free to contact our Integration'
            . ' Team at: %3',
            $newReleaseData['version'],
            $this->escaper->escapeUrl($newReleaseData['url']),
            'integration@multisafepay.com'
        )->render();
    }
}
