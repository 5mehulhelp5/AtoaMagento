<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\Model;

use Atoa\AtoaPayment\Logger\AtoaPaymentLogger;
use Atoa\AtoaPayment\Model\Payment\Atoa;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

class RedirectUrl
{
    private const END_POINT = 'https://api.atoa.me/api/payments/process-payment';

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var AtoaPaymentLogger
     */
    private AtoaPaymentLogger $logger;

    /**
     * @var CurlFactory
     */
    private CurlFactory $curlFactory;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * RedirectUrl construct.
     *
     * @param ConfigProvider $configProvider
     * @param AtoaPaymentLogger $logger
     * @param CurlFactory $curlFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ConfigProvider $configProvider,
        AtoaPaymentLogger $logger,
        CurlFactory $curlFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->curlFactory = $curlFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Request
     *
     * @param Order $order
     * @param string $paymentType
     * @return string
     * @throws NoSuchEntityException
     */
    public function getRedirectUrl(Order $order, string $paymentType = 'PAY_BY_BANK'): string
    {
        $this->logger->info('[REQUEST_REDIRECT]');
        $data = [
            'customerId' => $order->getBillingAddress()->getEmail(),
            'orderId' => $order->getIncrementId(),
            'amount' => $order->getGrandTotal(),
            'currency' => $order->getOrderCurrency() ? $order->getOrderCurrency()->getCode() : 'GBP',
            'paymentType' => strtoupper($paymentType),
            "paymentMethod" => [strtoupper($paymentType)],
            'autoRedirect' => false,
            'consumerDetails' => [
                'phoneCountryCode' => CountryPhoneCode::PHONE_CODE[$order->getBillingAddress()->getCountryId()],
                'phoneNumber' => preg_replace('/[^0-9]/', '', $order->getBillingAddress()->getTelephone()),
                'email' => $order->getBillingAddress()->getEmail(),
                'firstName' => $order->getBillingAddress()->getFirstname(),
                'lastName' => $order->getBillingAddress()->getLastname()
            ],
            'redirectUrl' => $this->storeManager->getStore()->getBaseUrl() . 'atoa/callback',
        ];
        
        $this->logger->info('[REQUEST_END_POINT]', [self::END_POINT]);
        $this->logger->info('[REQUEST_PARAMS]', [$data]);

        $curl = $this->curlFactory->create();

        $curl->setHeaders(
            [
                'Authorization' => 'Bearer ' . $this->configProvider->getConfig(Atoa::ACCESS_TOKEN),
                'Content-Type' => 'application/json'
            ]
        );
        $curl->post(self::END_POINT, json_encode($data));

        $response = json_decode($curl->getBody(), true);
        $this->logger->info('[RESPONSE_REDIRECT]', $response);

        return $response['paymentUrl'];
    }
}