<?php
namespace Wangjian\PinganPay\OptionResolver;

class GetOrderStatusOptionResolver extends AbstractOptionResolver
{
    protected function configureOptions()
    {
        $this->resolver->setDefaults([
            'ord_no' => null,
            'out_no' => null
        ]);

        $this->resolver->setAllowedTypes('ord_no', ['string', 'null']);
        $this->resolver->setAllowedTypes('out_no', ['string', 'null']);
    }
}