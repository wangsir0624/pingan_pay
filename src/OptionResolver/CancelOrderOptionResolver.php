<?php
namespace Wangjian\PinganPay\OptionResolver;

class CancelOrderOptionResolver extends GetOrderStatusOptionResolver
{
    protected function configureOptions()
    {
        parent::configureOptions();

        $this->resolver->setDefault('sign_type', 'RSA');

        $this->resolver->setRequired('sign_type');

        $this->resolver->setAllowedTypes('sign_type', 'string');

        $this->resolver->setAllowedValues('sign_type', ['RSA', 'RSA2']);
    }
}