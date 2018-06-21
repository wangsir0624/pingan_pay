<?php
namespace Wangjian\PinganPay\OptionResolver;

class GetOrderListOptionResolver extends AbstractOptionResolver
{
    protected function configureOptions()
    {
        $this->resolver->setDefaults([
            'page' => null,
            'pagesize' => null,
            'out_no' => null,
            'trade_no' => null,
            'ord_no' => null,
            'pmt_tag' => null,
            'ord_type' => null,
            'status' => null,
            'sdate' => null,
            'edate' => null
        ]);

        $this->resolver->setAllowedTypes('page', ['int', 'string', 'null']);
        $this->resolver->setAllowedTypes('pagesize', ['int', 'string', 'null']);
        $this->resolver->setAllowedTypes('out_no', ['string', 'null']);
        $this->resolver->setAllowedTypes('trade_no', ['string', 'null']);
        $this->resolver->setAllowedTypes('ord_no', ['string', 'null']);
        $this->resolver->setAllowedTypes('pmt_tag', ['string', 'null']);
        $this->resolver->setAllowedTypes('ord_type', ['int', 'string', 'null']);
        $this->resolver->setAllowedTypes('status', ['int', 'string', 'null']);
        $this->resolver->setAllowedTypes('sdate', ['string', 'null']);
        $this->resolver->setAllowedTypes('edate', ['string', 'null']);

        $this->resolver->setAllowedValues('page', function ($value) {
            return is_null($value) || $value > 0;
        });
        $this->resolver->setAllowedValues('pagesize', function($value) {
            return is_null($value) || $value > 0 && $value <= 100;
        });
        $this->resolver->setAllowedValues('ord_type', [1, 2, null]);
        $this->resolver->setAllowedValues('status', [1, 2, 4, 9, null]);
        $this->resolver->setAllowedValues('sdate', function($value) {
            return is_null($value) || preg_match('/^\d{8}$/', $value);
        });
        $this->resolver->setAllowedValues('edate', function($value) {
            return is_null($value) || preg_match('/^\d{8}$/', $value);
        });
    }
}