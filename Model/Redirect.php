<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\Model;

use Atoa\AtoaPayment\Api\RedirectInterface;
use Atoa\AtoaPayment\Model\Data\RedirectFactory;
use Atoa\AtoaPayment\Model\Payment\Atoa;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Exception\SessionException;
use Magento\Sales\Model\Order;

class Redirect implements RedirectInterface
{
    /**
     * @var RedirectFactory
     */
    private RedirectFactory $redirectDataFactory;

    /**
     * @var Session
     */
    private Session $checkoutSession;

    /**
     * @var RedirectUrl
     */
    private RedirectUrl $redirectUrl;

    /**
     * Redirect construct.
     *
     * @param RedirectFactory $redirectDataFactory
     * @param Session $checkoutSession
     * @param RedirectUrl $redirectUrl
     */
    public function __construct(
        RedirectFactory $redirectDataFactory,
        Session $checkoutSession,
        RedirectUrl $redirectUrl
    ) {
        $this->redirectDataFactory = $redirectDataFactory;
        $this->checkoutSession = $checkoutSession;
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * Redirect
     *
     * @param mixed $orderId
     * @param string $paymentType
     * @return \Atoa\AtoaPayment\Api\Data\RedirectDataInterface
     * @throws AuthorizationException
     * @throws InputException
     * @throws LocalizedException
     * @throws PaymentException
     * @throws SessionException
     * @throws \JsonException
     */
    public function redirect(mixed $orderId, string $paymentType = 'PAY_BY_BANK')
    {
        if (!in_array($paymentType, Atoa::ALLOWED_PAYMENT_TYPES, true)) {
            throw new InputException(
                __('Invalid payment type. Allowed values: %1', implode(', ', Atoa::ALLOWED_PAYMENT_TYPES))
            );
        }

        $order = $this->loadOrder((int)$orderId);
        $payment = $order->getPayment();

        if (!$payment) {
            throw new PaymentException(
                __('Cannot retrieve a payment detail from the request,
                 please contact our support if you have any questions')
            );
        }

        $method = $payment->getMethod();
        if ($method !== Atoa::CODE && $method !== Atoa::CODE_CARD) {
            throw new PaymentException(
                __('Cannot retrieve a payment detail from the request,
                 please contact our support if you have any questions')
            );
        }

        $data = $this->redirectDataFactory->create();
        $data->setRedirectUrl($this->redirectUrl->getRedirectUrl($order, $paymentType));
        return $data;
    }

    /**
     * Load Order
     *
     * @param int $orderId
     * @return Order
     * @throws AuthorizationException
     * @throws SessionException
     */
    private function loadOrder(int $orderId): Order
    {
        $order = $this->checkoutSession->getLastRealOrder();

        if (!$order->getId()) {
            throw new SessionException(
                __('Your order session is no longer exists.
                 In case that your payment transaction has been completed,
                  please kindly check your payment transaction with the bank.
                   In case that the payment has not been completed, you can make an order and complete payment again.')
            );
        }

        if ($orderId !== (int)$order->getId()) {
            throw new AuthorizationException(
                __('This request is not authorized to access the resource,
                 please contact our support if you have any questions')
            );
        }

        return $order;
    }
}
