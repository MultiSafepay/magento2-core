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

namespace MultiSafepay\ConnectCore\Plugin;

use Closure;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;

class CsrfValidatorSkip
{
    private const MODULE_NAME = 'multisafepay';
    private const ACTION_NAME = 'notification';

    /**
     * @param CsrfValidator $subject
     * @param Closure $proceed
     * @param RequestInterface $request
     * @param ActionInterface $action
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundValidate(
        CsrfValidator $subject,
        Closure $proceed,
        RequestInterface $request,
        ActionInterface $action
    ): void {
        if ($request->getModuleName() === self::MODULE_NAME && $request->getActionName() === self::ACTION_NAME) {
            return;
        }

        $proceed($request, $action);
    }
}
