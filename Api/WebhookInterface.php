<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\Api;

use Atoa\AtoaPayment\Api\Data\StatusDetailsDataInterface;
use Atoa\AtoaPayment\Api\Data\StoreDetailsDataInterface;
interface WebhookInterface
{
    /**
     * Execute
     *
     * @param ?string $merchantId
     * @param ?string $customerId
     * @param ?string $consumerId
     * @param ?string $merchantName
     * @param ?string $paymentIdempotencyId
     * @param ?string $status
     * @param StatusDetailsDataInterface|null $statusDetails
     * @param ?string $paidAmount
     * @param ?string $tipAmount
     * @param ?string $currency
     * @param ?string $createdAt
     * @param ?string $updatedAt
     * @param ?string $taxAmount
     * @param ?string $serviceAmount
     * @param StoreDetailsDataInterface $storeDetails
     * @param ?string $orderId
     * @param ?string $paymentRequestId
     * @param mixed $redirectUrlParams
     * @param ?string $redirectUrl
     * @param ?string $signatureHash
     * @param ?string $errorDescription
     * @param ?string $eventType
     * @return WebhookInterface
     */
    public function execute(
        ?string $merchantId,
        ?string $customerId,
        ?string $consumerId,
        ?string $merchantName,
        ?string $paymentIdempotencyId,
        ?string $status,
        ?\Atoa\AtoaPayment\Api\Data\StatusDetailsDataInterface $statusDetails,
        ?string $paidAmount,
        ?string $tipAmount,
        ?string $currency,
        ?string $createdAt,
        ?string $updatedAt,
        ?string $taxAmount,
        ?string $serviceAmount,
        \Atoa\AtoaPayment\Api\Data\StoreDetailsDataInterface $storeDetails,
        ?string $orderId,
        ?string $paymentRequestId,
        mixed $redirectUrlParams,
        ?string $redirectUrl,
        ?string $signatureHash = null,
        ?string $errorDescription = null,
        ?string $eventType = null
    ): WebhookInterface;

    /**
     * Get response message.
     *
     * @return ?string
     */
    public function getMessage(): ?string;
}
