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

namespace MultiSafepay\ConnectCore\Cron;

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\RedirectTokenRepository;

class RedirectTokenCleanup
{
    private const DAYS_TO_KEEP = 7;

    /**
     * @var RedirectTokenRepository
     */
    private $redirectTokenRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * RedirectTokenCleanup constructor.
     *
     * @param RedirectTokenRepository $redirectTokenRepository
     * @param Logger $logger
     */
    public function __construct(
        RedirectTokenRepository $redirectTokenRepository,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->redirectTokenRepository = $redirectTokenRepository;
    }

    /**
     * Execute the cron job to clean up old redirect tokens.
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            $deleted = $this->redirectTokenRepository->deleteOlderThanDays(self::DAYS_TO_KEEP);
            $this->logger->info(
                sprintf('Redirect token cleanup: deleted %d rows older than %d days', $deleted, self::DAYS_TO_KEEP)
            );
        } catch (LocalizedException $exception) {
            $this->logger->logExceptionForCron($exception);
        }
    }
}
