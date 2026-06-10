<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\Model;

use Atoa\AtoaPayment\Api\Data\StatusDetailsDataInterface;
use Atoa\AtoaPayment\Api\Data\StoreDetailsDataInterface;
use Atoa\AtoaPayment\Api\WebhookInterface;
use Atoa\AtoaPayment\Model\Payment\Atoa;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice as OrderInvoice;

class Webhook extends AbstractWebhook implements WebhookInterface
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
     * @param ?string $signatureHash
     * @param DataObject $redirectUrlParams
     * @param ?string $redirectUrl
     * @param ?string $errorDescription
     * @return WebhookInterface
     * @throws AlreadyExistsException
     * @throws LocalizedException
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
        ?string $signatureHash,
        \Magento\Framework\DataObject $redirectUrlParams,
        ?string $redirectUrl,
        ?string $errorDescription = null
    ): WebhookInterface {
        $this->logger->info('*******************************************************************');
        $this->logger->info('[PROCESS_WEBHOOK_START]');
        $this->logger->info('[WEBHOOK_PARAMS]', [
            'merchant_id' => $merchantId,
            'customer_id' => $customerId,
            'consumer_id' => $consumerId,
            'merchant_name' => $merchantName,
            'payment_idempotency_id' => $paymentIdempotencyId,
            'status' => $status,
            'status_details' => [
                'status' => $statusDetails ? $statusDetails->getStatus() : '',
                'status_update_date' => $statusDetails ? $statusDetails->getStatusUpdateDate() : '',
                'iso_status' => [
                    'name' => $statusDetails ? $statusDetails->getIsoStatus()->getName() : '',
                    'code' => $statusDetails ? $statusDetails->getIsoStatus()->getCode() : ''
                ]
            ],
            'paid_amount' => $paidAmount,
            'tip_amount' => $tipAmount,
            'currency' => $currency,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'tax_amount' => $taxAmount,
            'service_amount' => $serviceAmount,
            'store_details' => [
                'id' => $storeDetails->getId(),
                'address' => $storeDetails->getAddress(),
                'location_name' => $storeDetails->getLocationName()
            ],
            'order_id' => $orderId,
            'payment_request_id' => $paymentRequestId,
            'signature_hash' => $signatureHash,
            'error_description' => $errorDescription,
            'redirect_url_params' => $redirectUrlParams->getData(),
            'redirect_url' => $redirectUrl
        ]);
        $this->logger->info('[WEBHOOK_PARAMS_END]');

        if (!$this->validateRequest($orderId, $paymentRequestId, $signatureHash)) {
            $this->logger->info('[PROCESS_WEBHOOK_END]', ['signature hash not match']);
            $this->logger->info('*******************************************************************');
            return $this;
        }

        /** @var Order $order */
        $order = $this->collectionFactory->create()
            ->addFieldToFilter('increment_id', ['eq' => $orderId])
            ->addFieldToFilter('customer_email', ['eq' => $customerId])
            ->setPageSize(1)
            ->getFirstItem();

        if (!$order->getId() || $order->getPayment()->getMethod() !== Atoa::CODE) {
            $this->logger->info('[PROCESS_WEBHOOK_END]', ['order not found']);
            $this->logger->info('*******************************************************************');
            return $this;
        }

        if ($status === Atoa::PAYMENT_STATUS_PENDING) {
            $order->addCommentToStatusHistory(
                __(
                    'Authorized amount of %1 %2 online. Pay request ID: %3 ',
                    $paidAmount,
                    $currency,
                    $paymentRequestId
                )->__toString(),
                $this->configProvider->getConfig(Atoa::NEW_ORDER_STATUS)
            );
        }

        if ($status === Atoa::PAYMENT_STATUS_COMPLETED) {
            $order->addCommentToStatusHistory(
                __(
                    'Captured amount of %1 %2 online. Pay request ID: %3 ',
                    $paidAmount,
                    $currency,
                    $paymentRequestId
                )->__toString(),
                $this->configProvider->getConfig(Atoa::PAID_ORDER_STATUS)
            );
            if ($this->configProvider->getConfig(Atoa::CREATE_AUTO_INVOICE)) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(OrderInvoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->save();
                $transactionSave = $this->transaction->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );
                $transactionSave->save();
                $this->invoiceService->notify((int)$invoice->getId());
            }
        }

        if ($status === Atoa::PAYMENT_STATUS_FAILED
            && $this->configProvider->getConfig(Atoa::CANCEL_ORDER_STATUS) === 'canceled'
            && $order->canCancel()
        ) {
            $order->cancel()
                ->addCommentToStatusHistory(
                    __('Canceled order. Pay request ID: %1 ', $paymentRequestId)->__toString()
                );
        }

        $this->resourceOrder->save($order);
        if ($order->getState() === Order::STATE_CANCELED) {
            $this->orderSender->send(
                $order,
                true,
                __('Canceled order. Pay request ID: %1 ', $paymentRequestId)->__toString()
            );
        }
        $this->logger->info('[PROCESS_WEBHOOK_END]', ['process order successfully']);
        $this->logger->info('*******************************************************************');
        return $this;
    }
}
