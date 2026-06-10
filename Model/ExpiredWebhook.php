<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\Model;

use Atoa\AtoaPayment\Api\Data\StoreDetailsDataInterface;
use Atoa\AtoaPayment\Api\ExpiredWebhookInterface;
use Atoa\AtoaPayment\Model\Payment\Atoa;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class ExpiredWebhook extends AbstractWebhook implements ExpiredWebhookInterface
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
     * @throws AlreadyExistsException
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
        ?string $redirectUrl,
    ): ExpiredWebhookInterface {
        $this->logger->info('*******************************************************************');
        $this->logger->info('[PROCESS_EXPIRED_WEBHOOK_START]');
        $this->logger->info('[EXPIRED_WEBHOOK_PARAMS]', [
            'merchant_id' => $merchantId,
            'customer_id' => $customerId,
            'status' => $status,
            'paid_amount' => $paidAmount,
            'currency' => $currency,
            'store_details' => [
                'id' => $storeDetails->getId(),
                'address' => $storeDetails->getAddress(),
                'location_name' => $storeDetails->getLocationName()
            ],
            'order_id' => $orderId,
            'payment_request_id' => $paymentRequestId,
            'signature_hash' => $signatureHash,
            'redirect_url' => $redirectUrl
        ]);
        $this->logger->info('[EXPIRED_WEBHOOK_PARAMS_END]');

        if (!$this->validateRequest($orderId, $paymentRequestId, $signatureHash)) {
            $this->logger->info('[PROCESS_EXPIRED_WEBHOOK_END]', ['signature hash not match']);
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
            $this->logger->info('[PROCESS_EXPIRED_WEBHOOK_END]', ['order not found']);
            $this->logger->info('*******************************************************************');
            return $this;
        }

        if ($status === Atoa::PAYMENT_STATUS_EXPIRED) {
            if ($order->canCancel()) {
                $order->cancel()->addCommentToStatusHistory(
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

            $this->logger->info('[PROCESS_EXPIRED_WEBHOOK_END]', ['revert quote successfully']);
            $this->logger->info('*******************************************************************');
        }
        return $this;
    }
}
