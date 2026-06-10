<?php

namespace Atoa\AtoaPayment\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;

class Card extends AbstractMethod
{
    protected $_code = 'atoa_card';

    protected $_isOffline = false;

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return true;
    }
}