<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\Api;

use Atoa\AtoaPayment\Api\Data\StoreDetailsDataInterface;

interface ExpiredWebhookInterface
{
    /**
     * Execute
     *
     * @param ?string $merchantId
     * @param ?string $customerId
     * @param ?string $status
     * @param ?string $paidAmount
     * @param ?string $currency
     * @param StoreDetailsDataInterface $storeDetails
     * @param ?string $orderId
     * @param ?string $paymentRequestId
     * @param ?string $signatureHash
     * @param ?string $redirectUrl
     * @return ExpiredWebhookInterface
     */
    public function execute(
        ?string $merchantId,
        ?string $customerId,
        ?string $status,
        ?string $paidAmount,
        ?string $currency,
        \Atoa\AtoaPayment\Api\Data\StoreDetailsDataInterface $storeDetails,
        ?string $orderId,
        ?string $paymentRequestId,
        ?string $signatureHash,
        ?string $redirectUrl
    ): ExpiredWebhookInterface;
}
