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

namespace MultiSafepay\ConnectCore\Controller\Connect;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\ApiTokenUtil;
use Exception;

/**
 * Returns a fresh MultiSafepay API token for the current quote.
 *
 * The response is marked as non-cacheable so no upstream cache (FPC, Varnish, CDN)
 * can keep a stale token.
 */
class ApiToken extends Action implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var ApiTokenUtil
     */
    private $apiTokenUtil;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CheckoutSession $checkoutSession
     * @param ApiTokenUtil $apiTokenUtil
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        ApiTokenUtil $apiTokenUtil,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->apiTokenUtil = $apiTokenUtil;
        $this->logger = $logger;
    }

    /**
     * Retrieve the API token for the current quote and return it as a JSON response.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $result->setHeader('Pragma', 'no-cache', true);
        $result->setHeader('Expires', '0', true);

        try {
            $quote = $this->checkoutSession->getQuote();
            $tokenData = $this->apiTokenUtil->getApiTokenFromCache($quote);
            $apiToken = $tokenData['apiToken'] ?? '';

            if (!$apiToken) {
                return $result->setHttpResponseCode(500)->setData([
                    'success' => false,
                    'message' => 'Could not retrieve API token'
                ]);
            }

            return $result->setData([
                'success' => true,
                'apiToken' => $apiToken,
                'apiTokenLifeTime' => $tokenData['lifeTime'] ?? null
            ]);
        } catch (Exception $exception) {
            $this->logger->logExceptionForApiToken($exception);

            return $result->setHttpResponseCode(500)->setData([
                'success' => false,
                'message' => 'An error occurred while retrieving the API token'
            ]);
        }
    }
}
