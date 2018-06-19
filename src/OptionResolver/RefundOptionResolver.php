<?php
namespace Wangjian\PinganPay\OptionResolver;

class RefundOptionResolver extends AbstractOptionResolver
{
    protected function configureOptions()
    {
        $this->resolver->setDefaults([
            'sign_type' => 'RSA',
            'refund_ord_name' => null,
            'trade_account' => null,
            'trade_no' => null,
            'trade_result' => null,
            'tml_token' => null,
            'remark' => null,
            'shop_pass' => '123456'
        ]);

        $this->resolver->setRequired([
            'sign_type',
            'out_no',
            'refund_out_no',
            'refund_amount',
            'shop_pass'
        ]);

        $this->resolver->setAllowedTypes('sign_type', 'string');
        $this->resolver->setAllowedTypes('out_no', 'string');
        $this->resolver->setAllowedTypes('refund_out_no', 'string');
        $this->resolver->setAllowedTypes('refund_ord_name', ['string', 'null']);
        $this->resolver->setAllowedTypes('refund_amount', 'int');
        $this->resolver->setAllowedTypes('trade_account', ['string', 'null']);
        $this->resolver->setAllowedTypes('trade_no', ['string', 'null']);
        $this->resolver->setAllowedTypes('trade_result', ['string', 'null']);
        $this->resolver->setAllowedTypes('tml_token', ['string', 'null']);
        $this->resolver->setAllowedTypes('remark', ['string', 'null']);
        $this->resolver->setAllowedTypes('shop_pass', 'string');

        $this->resolver->setAllowedValues('sign_type', ['RSA', 'RSA2']);
        $this->resolver->setAllowedValues('refund_amount', function($value) {
            return $value > 0;
        });
    }
}