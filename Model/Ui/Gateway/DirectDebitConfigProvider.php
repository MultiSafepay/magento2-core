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

namespace MultiSafepay\ConnectCore\Model\Ui\Gateway;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;

class DirectDebitConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_directdebit';

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * PayafterConfigProvider constructor.
     *
     * @param AssetRepository $assetRepository
     * @param Config $config
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        AssetRepository $assetRepository,
        Config $config,
        ResolverInterface $localeResolver
    ) {
        $this->assetRepository = $assetRepository;
        $this->localeResolver = $localeResolver;
        parent::__construct($assetRepository, $config);
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getImage(): string
    {
        $path = $this->getPath();
        $this->assetRepository->createAsset($path);

        return $this->assetRepository->getUrl($path);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        if ($this->localeResolver->getLocale() === 'nl_NL') {
            return 'MultiSafepay_ConnectFrontend::images/' . $this->getCode() . '-nl.png';
        }

        return 'MultiSafepay_ConnectFrontend::images/' . $this->getCode() . '-en.png';
    }
}
