<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\ViewModel;

use Atoa\AtoaPayment\Model\ConfigProvider;
use Atoa\AtoaPayment\Model\Payment\Atoa;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Display implements ArgumentInterface
{
    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * Display construct.
     *
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * Is Enable Information Popup
     *
     * @return bool
     */
    public function isEnableInformationPopup(): bool
    {
        return $this->configProvider->isEnableInformationPopup();
    }

    /**
     * Get Display Styles
     *
     * @return string
     */
    public function getDisplayStyles(): string
    {
        return $this->configProvider->getConfig(Atoa::BANNER_STYLES);
    }

    /**
     * Get Banner Content
     *
     * @return array
     */
    public function getBannerContent(): array
    {
        $contentText = $this->configProvider->getConfig(Atoa::BANNER_CONTENT_TEXT);
        if ($contentText) {
            $parts = preg_split('/(\{\{logo\}\})/', $contentText, -1, PREG_SPLIT_DELIM_CAPTURE);

            return array_values(array_filter($parts, function ($part) {
                return $part !== '';
            }));
        }
        return [];
    }
}
