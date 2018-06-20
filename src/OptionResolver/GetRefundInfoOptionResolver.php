<?php
namespace Wangjian\PinganPay\OptionResolver;

class GetRefundInfoOptionResolver extends AbstractOptionResolver
{
    protected function configureOptions()
    {
        $this->resolver->setDefaults([
            'refund_out_no' => null,
            'refund_ord_no' => null
        ]);

        $this->resolver->setAllowedTypes('refund_out_no', ['string', 'null']);
        $this->resolver->setAllowedTypes('refund_ord_no', ['string', 'null']);
    }
}