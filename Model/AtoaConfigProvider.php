<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\Model;

use Atoa\AtoaPayment\Model\Payment\Atoa;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;

class AtoaConfigProvider implements ConfigProviderInterface
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var Repository
     */
    private Repository $assetRepo;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * Atoa Config Provider construct.
     *
     * @param RequestInterface $request
     * @param Repository $assetRepo
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        RequestInterface $request,
        Repository $assetRepo,
        ConfigProvider $configProvider
    ) {
        $this->request = $request;
        $this->assetRepo = $assetRepo;
        $this->configProvider = $configProvider;
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        $commonLogoMark = $this->assetRepo->getUrlWithParams(
            'Atoa_AtoaPayment/images/atoa-claret-icon.png',
            ['_secure' => $this->request->isSecure()]
        );

        // Bank payment method config
        $bankConfig = [
            'logoMarkHref' => $commonLogoMark,
            'bankLogos' => [
                $this->assetRepo->getUrlWithParams(
                    'Atoa_AtoaPayment/images/banks/monzo.webp',
                    ['_secure' => $this->request->isSecure()]
                ),
                $this->assetRepo->getUrlWithParams(
                    'Atoa_AtoaPayment/images/banks/barclays.webp',
                    ['_secure' => $this->request->isSecure()]
                ),
                $this->assetRepo->getUrlWithParams(
                    'Atoa_AtoaPayment/images/banks/natwest.webp',
                    ['_secure' => $this->request->isSecure()]
                ),
                $this->assetRepo->getUrlWithParams(
                    'Atoa_AtoaPayment/images/banks/hsbc.webp',
                    ['_secure' => $this->request->isSecure()]
                ),
                $this->assetRepo->getUrlWithParams(
                    'Atoa_AtoaPayment/images/banks/bank_of_scotland.webp',
                    ['_secure' => $this->request->isSecure()]
                ),
                $this->assetRepo->getUrlWithParams(
                    'Atoa_AtoaPayment/images/banks/lloyds.webp',
                    ['_secure' => $this->request->isSecure()]
                ),
                $this->assetRepo->getUrlWithParams(
                    'Atoa_AtoaPayment/images/banks/santander.webp',
                    ['_secure' => $this->request->isSecure()]
                ),
                $this->assetRepo->getUrlWithParams(
                    'Atoa_AtoaPayment/images/banks/tsb.webp',
                    ['_secure' => $this->request->isSecure()]
                )
            ],
            'bannerCheckoutText' => $this->configProvider->getConfig(Atoa::BANNER_CHECKOUT_TEXT, Atoa::CODE) ?: 'Pay securely via your Bank app',
            'style' => $this->configProvider->getConfig(Atoa::BANNER_CHECKOUT_STYLES, Atoa::CODE) ?: '1'
        ];

        // Card payment method config
        $cardConfig = [
            'logoMarkHref' => $commonLogoMark,
            'cardLogos' => [
                $this->assetRepo->getUrlWithParams(
                    'Atoa_AtoaPayment/images/cards/visa.svg',
                    ['_secure' => $this->request->isSecure()]
                ),
                $this->assetRepo->getUrlWithParams(
                    'Atoa_AtoaPayment/images/cards/master.svg',
                    ['_secure' => $this->request->isSecure()]
                )
            ],
            'bannerCheckoutText' => $this->configProvider->getConfig('banner_checkout_text', Atoa::CODE_CARD) ?: 'Pay securely via your Card',
            'style' => $this->configProvider->getConfig('banner_styles', Atoa::CODE_CARD) ?: '1'
        ];
        
        return [
            'payment' => [
                Atoa::CODE => $bankConfig,
                Atoa::CODE_CARD => $cardConfig
            ]
        ];
    }
}
