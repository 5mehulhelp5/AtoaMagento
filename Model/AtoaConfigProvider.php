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
        return [
            'payment' => [
                Atoa::CODE => [
                    'logoMarkHref' => $this->getAssetUrl('Atoa_AtoaPayment/images/atoa-claret-icon.png'),
                    'bankConfig'   => [
                        'logos' => $this->getBankLogos(),
                    ],
                    'bannerCheckoutText' => $this->configProvider->getConfigForMethod(
                        Atoa::CODE,
                        Atoa::BANNER_CHECKOUT_TEXT
                    ),
                    'style'              => $this->configProvider->getConfigForMethod(
                        Atoa::CODE,
                        Atoa::BANNER_CHECKOUT_STYLES
                    ),
                ],
                Atoa::CODE_CARD => [
                    'cardConfig'         => [
                        'logos' => $this->getCardLogos(),
                    ],
                    'bannerCheckoutText' => $this->configProvider->getConfigForMethod(
                        Atoa::CODE_CARD,
                        Atoa::BANNER_CHECKOUT_TEXT
                    ),
                    'style'              => $this->configProvider->getConfigForMethod(
                        Atoa::CODE_CARD,
                        Atoa::BANNER_CHECKOUT_STYLES
                    ),
                ],
            ]
        ];
    }

    /**
     * Return individual bank logo configs for the checkout UI.
     *
     * @return array<int, array{src: string, alt: string}>
     */
    private function getBankLogos(): array
    {
        $banks = [
            ['file' => 'monzo.webp',           'alt' => 'Monzo'],
            ['file' => 'barclays.webp',         'alt' => 'Barclays'],
            ['file' => 'natwest.webp',           'alt' => 'NatWest'],
            ['file' => 'hsbc.webp',             'alt' => 'HSBC'],
            ['file' => 'bank_of_scotland.webp', 'alt' => 'Bank of Scotland'],
            ['file' => 'lloyds.webp',           'alt' => 'Lloyds'],
            ['file' => 'santander.webp',        'alt' => 'Santander'],
            ['file' => 'tsb.webp',              'alt' => 'TSB'],
        ];

        return array_map(function (array $bank): array {
            return [
                'src' => $this->getAssetUrl('Atoa_AtoaPayment/images/banks/' . $bank['file']),
                'alt' => $bank['alt'],
            ];
        }, $banks);
    }

    /**
     * Return card logo configs for the checkout UI.
     *
     * @return array<int, array{src: string, alt: string}>
     */
    private function getCardLogos(): array
    {
        $cards = [
            ['file' => 'visa.svg',   'alt' => 'Visa'],
            ['file' => 'master.svg', 'alt' => 'Mastercard'],
        ];

        return array_map(function (array $card): array {
            return [
                'src' => $this->getAssetUrl('Atoa_AtoaPayment/images/cards/' . $card['file']),
                'alt' => $card['alt'],
            ];
        }, $cards);
    }

    /**
     * Build a secure-aware URL for a module asset.
     *
     * @param string $fileId
     * @return string
     */
    private function getAssetUrl(string $fileId): string
    {
        return $this->assetRepo->getUrlWithParams(
            $fileId,
            ['_secure' => $this->request->isSecure()]
        );
    }
}
