<?php

declare(strict_types=1);

namespace Atoa\AtoaPayment\Model;

use Atoa\AtoaPayment\Logger\AtoaPaymentLogger;
use Atoa\AtoaPayment\Model\Payment\Atoa;
use Magento\Checkout\Model\Session;
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
     */
    public function __construct(
        ConfigProvider $configProvider,
        AtoaPaymentLogger $logger,
        CollectionFactoryInterface $collectionFactory,
        ResourceOrder $resourceOrder,
        InvoiceService $invoiceService,
        Transaction $transaction,
        OrderCommentSender $orderSender,
        Session $checkoutSession
    ) {
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->resourceOrder = $resourceOrder;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->orderSender = $orderSender;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Validate Request
     *
     * @param string $orderId
     * @param string $paymentRequestId
     * @param string $signatureHash
     * @return bool
     */
    protected function validateRequest(string $orderId, string $paymentRequestId, string $signatureHash): bool
    {
        $accessToken = $this->configProvider->getConfig(Atoa::ACCESS_TOKEN);
        $signature = hash_hmac('sha256', $orderId . '|' . $paymentRequestId, $accessToken);
        return $signature === $signatureHash;
    }
}
