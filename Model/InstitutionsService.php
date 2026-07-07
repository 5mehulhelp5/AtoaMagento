<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;

class InstitutionsService
{
    private const INSTITUTIONS_URL = 'https://api.atoa.me/api/institutions/customer';
    private const CACHE_KEY        = 'atoa_bank_logos';
    private const CACHE_LIFETIME   = 86400; // 1 day

    private CurlFactory $curlFactory;
    private CacheInterface $cache;
    private Json $serializer;

    public function __construct(
        CurlFactory $curlFactory,
        CacheInterface $cache,
        Json $serializer
    ) {
        $this->curlFactory = $curlFactory;
        $this->cache       = $cache;
        $this->serializer  = $serializer;
    }

    /**
     * Returns deduplicated list of enabled bank logos from the institutions API.
     * Result is cached for one day.
     *
     * @return array<int, array{src: string, alt: string}>
     */
    public function getBankLogos(): array
    {
        $cached = $this->cache->load(self::CACHE_KEY);

        if ($cached !== false) {
            return $this->serializer->unserialize($cached);
        }

        $logos = $this->buildBankLogos();

        $this->cache->save(
            $this->serializer->serialize($logos),
            self::CACHE_KEY,
            [],
            self::CACHE_LIFETIME
        );

        return $logos;
    }

    /**
     * Builds the deduplicated logo list from the raw institutions API response.
     *
     * @return array<int, array{src: string, alt: string}>
     */
    private function buildBankLogos(): array
    {
        $logos    = [];
        $seenUrls = [];

        foreach ($this->fetchInstitutions() as $institution) {
            if (empty($institution['enabled'])) {
                continue;
            }

            $source = $this->extractIconUrl($institution['media'] ?? []);

            if (empty($source) || isset($seenUrls[$source])) {
                continue;
            }

            $seenUrls[$source] = true;
            $logos[] = [
                'src' => $source,
                'alt' => $institution['fullName'] ?? '',
            ];
        }

        return $logos;
    }

    /**
     * Fetches the raw institutions list from the Atoa API.
     *
     * @return array
     */
    private function fetchInstitutions(): array
    {
        try {
            $curl = $this->curlFactory->create();
            $curl->get(self::INSTITUTIONS_URL);

            if ($curl->getStatus() !== 200) {
                return [];
            }

            $data = json_decode($curl->getBody(), true);

            return (json_last_error() === JSON_ERROR_NONE && is_array($data)) ? $data : [];

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Extracts the icon-type URL from a media array.
     *
     * @param  array $media
     * @return string  Empty string if no icon entry is found.
     */
    private function extractIconUrl(array $media): string
    {
        foreach ($media as $item) {
            if (($item['type'] ?? '') === 'icon' && !empty($item['source'])) {
                return $item['source'];
            }
        }
        return '';
    }
}
