<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\Model;

use Atoa\AtoaPayment\Api\RedirectInterface;
use Atoa\AtoaPayment\Model\Data\RedirectFactory;
use Atoa\AtoaPayment\Model\Payment\Atoa;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Exception\SessionException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class Redirect implements RedirectInterface
{
    /**
     * @var RedirectFactory
     */
    private RedirectFactory $redirectDataFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

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
        OrderRepositoryInterface $orderRepository,
        RedirectUrl $redirectUrl
    ) {
        $this->redirectDataFactory = $redirectDataFactory;
        $this->orderRepository = $orderRepository;
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * Redirect
     *
     * @param mixed $orderId
     * @param string $paymentType
     * @return \Atoa\AtoaPayment\Api\Data\RedirectDataInterface
     * @throws AuthorizationException
     * @throws LocalizedException
     * @throws PaymentException
     * @throws SessionException
     * @throws \JsonException
     */
    public function redirect(mixed $orderId, string $paymentType = 'PAY_BY_BANK')
    {
        $order = $this->loadOrder((int)$orderId);

        if ($payment = $order->getPayment()) {
            if ($payment->getMethod() !== Atoa::CODE && $payment->getMethod() !== Atoa::CODE_CARD) {
                throw new AuthorizationException(
                    __('This request is not authorized to access the resource, please contact our support if you have any questions')
                );
            }

            $data = $this->redirectDataFactory->create();
            $data->setRedirectUrl($this->redirectUrl->getRedirectUrl($order, $paymentType));

            return $data;
        }

        throw new PaymentException(
            __('Cannot retrieve a payment detail from the request,
             please contact our support if you have any questions')
        );
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
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException $e) {
            throw new SessionException(
                __('Your order session is no longer exists. In case that your payment transaction has been completed, please kindly check your payment transaction with the bank. In case that the payment has not been completed, you can make an order and complete payment again.')
            );
        }

        if (!$order->getId()) {
            throw new SessionException(
                __('Your order session is no longer exists. In case that your payment transaction has been completed, please kindly check your payment transaction with the bank. In case that the payment has not been completed, you can make an order and complete payment again.')
            );
        }

        return $order;
    }
}
