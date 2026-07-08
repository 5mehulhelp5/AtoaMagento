<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\Model\Payment;

class Atoa
{
    /**
     * @var string
     */
    public const CODE = 'atoa';
    public const CODE_CARD = 'atoa_card';

    /** @var string[] Allowed values for the paymentType URL parameter */
    public const ALLOWED_PAYMENT_TYPES = ['PAY_BY_BANK', 'CARD'];

    public const ACTIVE = 'active';
    public const IS_SANDBOX = 'is_sandbox';
    public const ACCESS_TOKEN = 'access_token';
    public const WEBHOOK_URL = 'webhook_url';
    public const WEBHOOK_SIGNING_SECRET = 'webhook_signing_secret';
    public const DEFAULT_CALLBACK_STORE = 'default_callback_store';
    public const NEW_ORDER_STATUS = 'order_status';
    public const PAID_ORDER_STATUS = 'order_status_paid';
    public const CANCEL_ORDER_STATUS = 'order_status_cancel';
    public const CREATE_AUTO_INVOICE = 'create_auto_invoice';
    public const ENABLE_STORE_VIEW_BANNER = 'atoa_display_settings/enable_store_view_banner';
    public const ENABLE_BANNER_PRODUCT_LISTING = 'atoa_display_settings/enable_banner_product_listing';
    public const ENABLE_BANNER_PRODUCT_DETAIL = 'atoa_display_settings/enable_banner_product_detail';
    public const ENABLE_INFORMATION_POPUP = 'atoa_display_settings/enable_information_popup';
    public const BANNER_STYLES = 'atoa_display_settings/banner_styles';
    public const BANNER_CHECKOUT_STYLES = 'atoa_checkout_setting/banner_styles';
    public const BANNER_CHECKOUT_TEXT = 'atoa_checkout_setting/banner_checkout_text';
    public const BANNER_CONTENT_TEXT = 'atoa_display_settings/banner_content_text';

    public const SOURCE = 'magento';

    public const PAYMENT_STATUS_COMPLETED = 'COMPLETED';
    public const PAYMENT_STATUS_PENDING = 'PENDING';
    public const PAYMENT_STATUS_FAILED = 'FAILED';
    public const PAYMENT_STATUS_EXPIRED = 'EXPIRED';
}
