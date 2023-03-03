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

use Magento\AdminNotification\Model\InboxFactory;
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
     * @var FlagManager
     */
    private $flagManager;

    /**
     * NotificationUtil constructor.
     *
     * @param VersionUtil $versionUtil
     * @param Escaper $escaper
     * @param InboxFactory $inboxFactory
     * @param FlagManager $flagManager
     */
    public function __construct(
        VersionUtil $versionUtil,
        Escaper $escaper,
        InboxFactory $inboxFactory,
        FlagManager $flagManager
    ) {
        $this->versionUtil = $versionUtil;
        $this->escaper = $escaper;
        $this->inboxFactory = $inboxFactory;
        $this->flagManager = $flagManager;
    }

    /**
     * @return void
     */
    public function addNewReleaseNotification(): void
    {
        $newReleaseData = $this->versionUtil->getNewVersionsDataIfExist();

        if ($newReleaseData
            && version_compare(
                $newReleaseData['version'],
                (string)$this->flagManager->getFlagData(self::MULTISAFEPAY_LAST_RELEASE_NOTIFICATION_CONFIG_PATH)
            )
        ) {
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
        return __('MultiSafepay: A new version of payment module %1 has been released', $newVersion)->render();
    }

    /**
     * @param array $newReleaseData
     * @return string
     */
    private function getReleaseNotificationDescription(array $newReleaseData): string
    {
        return __(
            'Please, upgrade to the last version: %1. Click "Read Details" for more information about this release'
            . 'If you have any questions regarding the plugin, feel free to contact our Integration'
            . ' Team at: %2',
            $newReleaseData['version'],
            'integration@multisafepay.com'
        )->render();
    }
}
