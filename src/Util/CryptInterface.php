<?php
namespace Wangjian\PinganPay\Util;

interface CryptInterface
{
    public function encrypt($raw);

    public function decrypt($cipher);
}