<?php
namespace Wangjian\PinganPay\OptionResolver;

class ChargeOptionResolver extends AbstractOptionResolver
{
    protected function configureOptions()
    {
        $this->resolver->setDefaults([
            'pmt_name' => null,
            'ord_name' => null,
            'discount_amount' => null,
            'ignore_amount' => null,
            'trade_account' => null,
            'trade_no' => null,
            'remark' => null,
            'tag' => null,
            'notify_url' => null,
            'sub_appid' => null,
            'trade_type' => null,
            'spbill_create_ip' => null,
            'scene_info' => null,
            'sub_openid' => null,
            'goods_tag' => null,
            'limit_pay' => null
        ]);

        $this->resolver->setRequired([
            'out_no',
            'pmt_tag',
            'original_amount',
            'trade_amount'
        ]);

        $this->resolver->setAllowedTypes('out_no', 'string');
        $this->resolver->setAllowedTypes('pmt_tag', 'string');
        $this->resolver->setAllowedTypes('pmt_name', ['string', 'null']);
        $this->resolver->setAllowedTypes('ord_name', ['string', 'null']);
        $this->resolver->setAllowedTypes('original_amount', 'int');
        $this->resolver->setAllowedTypes('discount_amount', ['int', 'null']);
        $this->resolver->setAllowedTypes('ignore_amount', ['int', 'null']);
        $this->resolver->setAllowedTypes('trade_amount', 'int');
        $this->resolver->setAllowedTypes('trade_account', ['string', 'null']);
        $this->resolver->setAllowedTypes('trade_no', ['string', 'null']);
        $this->resolver->setAllowedTypes('remark', ['string', 'null']);
        $this->resolver->setAllowedTypes('tag', ['string', 'null']);
        $this->resolver->setAllowedTypes('notify_url', ['string', 'null']);
        $this->resolver->setAllowedTypes('sub_appid', ['string', 'null']);
        $this->resolver->setAllowedTypes('trade_type', ['string', 'null']);
        $this->resolver->setAllowedTypes('spbill_create_ip', ['string', 'null']);
        $this->resolver->setAllowedTypes('scene_info', ['string', 'null']);
        $this->resolver->setAllowedTypes('sub_openid', ['string', 'null']);
        $this->resolver->setAllowedTypes('goods_tag', ['string', 'null']);
        $this->resolver->setAllowedTypes('limit_pay', ['string', 'null']);

        $this->resolver->setAllowedValues('original_amount', function($value) {
            return $value > 0;
        });
        $this->resolver->setAllowedValues('discount_amount', function($value) {
            return is_null($value) || $value >= 0;
        });
        $this->resolver->setAllowedValues('ignore_amount', function($value) {
            return is_null($value) || $value >= 0;
        });
        $this->resolver->setAllowedValues('trade_amount', function($value) {
            return $value > 0;
        });
        $this->resolver->setAllowedValues('notify_url', function($value) {
            return is_null($value) || preg_match('/^https?:\/\/[^\s]+$/', $value);
        });
        $this->resolver->setAllowedValues('trade_type', ['APP', 'MWEB', 'JSAPI', null]);
    }
}