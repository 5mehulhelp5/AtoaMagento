<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\Controller\Adminhtml\System\Config;

use Atoa\AtoaPayment\Logger\AtoaPaymentLogger;
use Atoa\AtoaPayment\Model\ConfigProvider;
use Atoa\AtoaPayment\Model\Payment\Atoa;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Store\Model\StoreManagerInterface;

class GenerateWebhookUrl extends Action
{
    private const END_POINT = 'https://api.atoa.me/api/webhook/integrations/magento';

    /**
     * @var CurlFactory
     */
    private CurlFactory $curlFactory;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var AtoaPaymentLogger
     */
    private AtoaPaymentLogger $logger;

    /**
     * @var WriterInterface
     */
    private WriterInterface $writer;

    /**
     * GenerateWebhookUrl construct.
     *
     * @param Context $context
     * @param CurlFactory $curlFactory
     * @param StoreManagerInterface $storeManager
     * @param AtoaPaymentLogger $logger
     * @param WriterInterface $writer
     */
    public function __construct(
        Context $context,
        CurlFactory $curlFactory,
        StoreManagerInterface $storeManager,
        AtoaPaymentLogger $logger,
        WriterInterface $writer
    ) {
        $this->curlFactory = $curlFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->writer = $writer;
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return void
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $this->logger->debug('[START GENERATE WEBHOOK URL]');
        try {
            $params = [
                'url' => $this->storeManager->getStore()->getBaseUrl() . 'rest/V1/atoa/webhook',
                'event' => 'PAYMENTS_STATUS'
            ];
            $this->logger->debug('[GENERATE WEBHOOK URL ENDPOINT]', [self::END_POINT]);
            $this->logger->debug('[GENERATE WEBHOOK URL PARAMS]', [$params]);
            $curl = $this->curlFactory->create();
            $curl->setHeaders([
                'Authorization' => 'Bearer ' . $this->_request->getParam('access_token'),
                'Content-Type' => 'application/json'
            ]);

            $curl->post(self::END_POINT, json_encode($params));
            $response = json_decode($curl->getBody(), true);
            $this->logger->debug('[GENERATE WEBHOOK URL RESPONSE]', $response);

            if (empty($response['url']) || (isset($response['status']) && (int)$response['status'] !== 200)) {
                return $result->setData([
                    'success' => false,
                    'url' => '',
                    'message' => __($response['message'])
                ]);
            }

            $curl = $this->curlFactory->create();
            $curl->setHeaders([
                'Authorization' => 'Bearer ' . $this->_request->getParam('access_token'),
                'Content-Type' => 'application/json'
            ]);
            $params = [
                'url' => $this->storeManager->getStore()->getBaseUrl() . 'rest/V1/atoa/expiredWebhook',
                'event' => 'EXPIRED_STATUS'
            ];

            $curl->post(self::END_POINT, json_encode($params));
            $this->logger->debug('[GENERATE WEBHOOK EXPIRED URL ENDPOINT]', [self::END_POINT]);
            $this->logger->debug('[GENERATE WEBHOOK EXPIRED URL PARAMS]', [$params]);
            $responseWebhookExpired = json_decode($curl->getBody(), true);
            $this->logger->debug('[GENERATE WEBHOOK EXPIRED URL RESPONSE]', $responseWebhookExpired);

            if (empty($responseWebhookExpired['url'])
                || (isset($responseWebhookExpired['status']) && (int)$responseWebhookExpired['status'] !== 200)
            ) {
                return $result->setData([
                    'success' => false,
                    'url' => '',
                    'message' => __($response['message'])
                ]);
            }

            $this->writer->save(ConfigProvider::MODULE_CONFIG_PREFIX . Atoa::WEBHOOK_URL, $response['url']);
            return $result->setData([
                'success' => true,
                'url' => $response['url'],
                'message' => __('Generate Webhook URL successfully!!!')
            ]);
        } catch (Exception $exception) {
            $this->logger->debug('[GENERATE WEBHOOK URL ERROR]', [$exception->getMessage()]);
            return $result->setData(
                [
                    'success' => false,
                    'message' => __('Something went wrong while generate Webhook URL')
                ]
            );
        }
    }
}
