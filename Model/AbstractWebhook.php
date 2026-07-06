<?php

declare(strict_types=1);

namespace Atoa\AtoaPayment\Model;

use Atoa\AtoaPayment\Logger\AtoaPaymentLogger;
use Atoa\AtoaPayment\Model\Payment\Atoa;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\ResourceModel\Order as ResourceOrder;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Sales\Model\Service\InvoiceService;

abstract class AbstractWebhook
{
    /**
     * @var ConfigProvider
     */
    protected ConfigProvider $configProvider;

    /**
     * @var AtoaPaymentLogger
     */
    protected AtoaPaymentLogger $logger;

    /**
     * @var CollectionFactoryInterface
     */
    protected CollectionFactoryInterface $collectionFactory;

    /**
     * @var ResourceOrder
     */
    protected ResourceOrder $resourceOrder;

    /**
     * @var InvoiceService
     */
    protected InvoiceService $invoiceService;

    /**
     * @var Transaction
     */
    protected Transaction $transaction;

    /**
     * @var OrderCommentSender
     */
    protected OrderCommentSender $orderSender;

    /**
     * @var Session
     */
    protected Session $checkoutSession;

    /**
     * @var HttpRequest
     */
    protected HttpRequest $request;

    /**
     * Webhook construct.
     *
     * @param ConfigProvider $configProvider
     * @param AtoaPaymentLogger $logger
     * @param CollectionFactoryInterface $collectionFactory
     * @param ResourceOrder $resourceOrder
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param OrderCommentSender $orderSender
     * @param Session $checkoutSession
     * @param HttpRequest $request
     */
    public function __construct(
        ConfigProvider $configProvider,
        AtoaPaymentLogger $logger,
        CollectionFactoryInterface $collectionFactory,
        ResourceOrder $resourceOrder,
        InvoiceService $invoiceService,
        Transaction $transaction,
        OrderCommentSender $orderSender,
        Session $checkoutSession,
        HttpRequest $request
    ) {
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->resourceOrder = $resourceOrder;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->orderSender = $orderSender;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
    }

    /**
     * Validate webhook request signature.
     * Tries V2 (X-Atoa-Signature header) first, falls back to V1 (signatureHash param).
     *
     * @param ?string $orderId
     * @param ?string $paymentRequestId
     * @param ?string $signatureHash
     * @return bool
     */
    protected function validateRequest(?string $orderId, ?string $paymentRequestId, ?string $signatureHash): bool
    {
        $signatureHeader = $this->request->getHeader('X-Atoa-Signature');

        if (is_string($signatureHeader) && $signatureHeader !== '') {
            $this->logger->info('[VALIDATE_REQUEST] V2 signature header detected');
            return $this->validateV2Signature($signatureHeader);
        }

        $this->logger->info('[VALIDATE_REQUEST] Using V1 signature verification');
        if (empty($orderId) || empty($paymentRequestId) || empty($signatureHash)) {
            $this->logger->info('[VALIDATE_REQUEST] No signature found');
            return false;
        }

        $accessToken = (string) $this->configProvider->getConfig(Atoa::ACCESS_TOKEN);
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentRequestId, $accessToken);
        return hash_equals($expected, $signatureHash);
    }

    /**
     * Verify V2 signature: HMAC-SHA256 of raw request body with signing secret.
     * Header format: "v1=<hex>", Secret format: "whsec_<base64>"
     *
     * @param string $signatureHeader
     * @return bool
     */
    private function validateV2Signature(string $signatureHeader): bool
    {
        $signingSecret = (string) $this->configProvider->getConfig(Atoa::WEBHOOK_SIGNING_SECRET);
        if (empty($signingSecret)) {
            $this->logger->info('[VALIDATE_REQUEST] No signing secret configured');
            return false;
        }

        $parts = explode('_', $signingSecret, 2);
        if (count($parts) < 2 || $parts[1] === '') {
            $this->logger->info('[VALIDATE_REQUEST] Invalid signing secret format');
            return false;
        }

        $secret = base64_decode($parts[1]);
        if ($secret === '' || $secret === false) {
            $this->logger->info('[VALIDATE_REQUEST] Failed to decode signing secret');
            return false;
        }

        $rawBody = (string) $this->request->getContent();
        $expected = 'v1=' . hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $signatureHeader);
    }
}
