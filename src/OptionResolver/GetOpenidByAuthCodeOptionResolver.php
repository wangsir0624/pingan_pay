<?php
namespace Wangjian\PinganPay\OptionResolver;

class GetOpenidByAuthCodeOptionResolver extends AbstractOptionResolver
{
    protected function configureOptions()
    {
        $this->resolver->setDefaults([
            'sub_appid' => null
        ]);

        $this->resolver->setRequired([
            'pmt_tag',
            'auth_code'
        ]);

        $this->resolver->setAllowedTypes('pmt_tag', 'string');
        $this->resolver->setAllowedTypes('auth_code', 'string');
        $this->resolver->setAllowedTypes('sub_appid', ['string', 'null']);
    }
}