<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\Controller\Callback;

use Atoa\AtoaPayment\Logger\AtoaPaymentLogger;
use Atoa\AtoaPayment\Model\ConfigProvider;
use Atoa\AtoaPayment\Model\Payment\Atoa;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Index implements HttpGetActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var RedirectFactory
     */
    private RedirectFactory $redirectFactory;

    /**
     * @var AtoaPaymentLogger
     */
    private AtoaPaymentLogger $logger;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var Session
     */
    private Session $checkoutSession;

    /**
     * Webhook construct.
     *
     * @param RequestInterface $request
     * @param RedirectFactory $redirectFactory
     * @param AtoaPaymentLogger $logger
     * @param ConfigProvider $configProvider
     * @param StoreManagerInterface $storeManager
     * @param Session $checkoutSession
     */
    public function __construct(
        RequestInterface $request,
        RedirectFactory $redirectFactory,
        AtoaPaymentLogger $logger,
        ConfigProvider $configProvider,
        StoreManagerInterface $storeManager,
        Session $checkoutSession
    ) {
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->logger = $logger;
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Execute
     *
     * @return Redirect
     * @throws NoSuchEntityException
     */
    public function execute(): Redirect
    {
        $params = $this->request->getParams();
        $this->logger->info('[CALLBACK_PARAMS]', $params);
        $resultRedirect = $this->redirectFactory->create();
        $redirectPath = 'checkout/onepage/failure';
        $restoreQuote = true;
        $order = $this->checkoutSession->getLastRealOrder();

        if (!empty($params) && $params['status'] === Atoa::PAYMENT_STATUS_EXPIRED || !$order->getId()) {
            $redirectPath = 'checkout/cart';
            $restoreQuote = (bool)$order->getId();
        }

        if (!empty($params)
            && isset($params['orderId'])
            && $this->validateRequest($params['orderId'], $params['paymentRequestId'], $params['atoaSignatureHash'])
            && $order->getId()
        ) {
            if ($params['status'] === Atoa::PAYMENT_STATUS_PENDING
                || $params['status'] === Atoa::PAYMENT_STATUS_COMPLETED
            ) {
                if ($params['status'] === Atoa::PAYMENT_STATUS_COMPLETED) {
                    $this->checkoutSession->setData(
                        'titlePage',
                        'Thank you for your purchase!'
                    );
                    $this->checkoutSession->setData(
                        'message',
                        ''
                    );
                }

                if ($params['status'] === Atoa::PAYMENT_STATUS_PENDING) {
                    $this->checkoutSession->setData(
                        'titlePage',
                        'Order Pending'
                    );
                    $this->checkoutSession->setData(
                        'message',
                        'Your order has been placed and your payment is under review.
                         We\'ll notify you when it\'s confirmed.'
                    );
                }
                $redirectPath = 'checkout/onepage/success';
                $restoreQuote = false;
            }

            if ($params['status'] === Atoa::PAYMENT_STATUS_FAILED) {
                $this->checkoutSession->setData(
                    'titlePage',
                    'Transaction Failed'
                );
                $this->checkoutSession->setData(
                    'message',
                    'Sorry, we couldn\'t process your payment. Please try again or use a different payment method.'
                );
            }
        }

        if ($restoreQuote === true) {
            $this->checkoutSession->restoreQuote();
        }

        $resultRedirect->setPath(
            $redirectPath,
            [
                '___store' => $this->storeManager->getStore(
                    (int)$this->configProvider->getConfig(Atoa::DEFAULT_CALLBACK_STORE)
                )->getCode()
            ]
        );
        return $resultRedirect;
    }

    /**
     * Create Csrf Validation Exception
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * Validate For Csrf
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(
        RequestInterface $request
    ): ?bool {
        return true;
    }

    /**
     * Validate Request
     *
     * @param string $orderId
     * @param string $paymentRequestId
     * @param string $signatureHash
     * @return bool
     */
    private function validateRequest(
        string $orderId,
        string $paymentRequestId,
        string $signatureHash
    ): bool {
        $accessToken = $this->configProvider->getConfig(Atoa::ACCESS_TOKEN);
        $signature = hash_hmac('sha256', $orderId . '|' . $paymentRequestId, $accessToken);
        return $signature === $signatureHash;
    }
}
