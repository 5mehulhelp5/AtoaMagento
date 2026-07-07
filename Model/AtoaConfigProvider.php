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
     * @var InstitutionsService
     */
    private InstitutionsService $institutionsService;

    /**
     * Atoa Config Provider construct.
     *
     * @param RequestInterface $request
     * @param Repository $assetRepo
     * @param ConfigProvider $configProvider
     * @param InstitutionsService $institutionsService
     */
    public function __construct(
        RequestInterface $request,
        Repository $assetRepo,
        ConfigProvider $configProvider,
        InstitutionsService $institutionsService
    ) {
        $this->request             = $request;
        $this->assetRepo           = $assetRepo;
        $this->configProvider      = $configProvider;
        $this->institutionsService = $institutionsService;
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
                        'logos' => $this->institutionsService->getBankLogos(),
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
     * Return card logo configs for the checkout UI.
     *
     * @return array<int, array{src: string, alt: string}>
     */
    private function getCardLogos(): array
    {
        $cards = [
            ['file' => 'visa.svg',      'alt' => 'Visa'],
            ['file' => 'master.svg',    'alt' => 'Mastercard'],
            ['file' => 'g_pay.svg',     'alt' => 'Google Pay'],
            ['file' => 'apple_pay.svg', 'alt' => 'Apple Pay'],
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
