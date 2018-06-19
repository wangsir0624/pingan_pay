<?php
namespace Wangjian\PinganPay\OptionResolver;

class GetOrderInfoOptionResolver extends AbstractOptionResolver
{
    protected function configureOptions()
    {
        $this->resolver->setRequired('out_no');
        $this->resolver->setAllowedTypes('out_no', 'string');
    }
}