<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\Api;

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
     * @param ?string $storeDetails
     * @param ?string $orderId
     * @param ?string $paymentRequestId
     * @param ?string $redirectUrl
     * @param mixed $redirectUrlParams
     * @param ?string $signatureHash
     * @param ?string $eventType
     * @return ExpiredWebhookInterface
     */
    public function execute(
        ?string $merchantId,
        ?string $customerId,
        ?string $status,
        ?string $paidAmount,
        ?string $currency,
        ?string $storeDetails,
        ?string $orderId,
        ?string $paymentRequestId,
        ?string $redirectUrl,
        mixed $redirectUrlParams = null,
        ?string $signatureHash = null,
        ?string $eventType = null
    ): ExpiredWebhookInterface;

    /**
     * Get response message.
     *
     * @return ?string
     */
    public function getMessage(): ?string;
}
