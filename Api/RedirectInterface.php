<?php
declare(strict_types=1);

namespace Atoa\AtoaPayment\Api;

interface RedirectInterface
{
    /**
     * Redirect
     *
     * @param mixed $orderId
     * @param string $paymentType
     * @return \Atoa\AtoaPayment\Api\Data\RedirectDataInterface
     */
    public function redirect(mixed $orderId, string $paymentType = 'PAY_BY_BANK');
}