<?php
namespace Wangjian\PinganPay\OptionResolver;

class GetBillsOptionResolver extends AbstractOptionResolver
{
    protected function configureOptions()
    {
        $this->resolver->setDefaults([
            'day' => date('Y-m-d', strtotime('-1 day')),
            'tar_type' => null,
            'pmt_tag' => null
        ]);

        $this->resolver->setAllowedTypes('day', 'string');
        $this->resolver->setAllowedTypes('tar_type', ['string', 'null']);
        $this->resolver->setAllowedTypes('pmt_tag', ['string', 'null']);

        $this->resolver->setAllowedValues('day', function($value) {
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
        });
        $this->resolver->setAllowedValues('tar_type', ['gzip', null]);
    }
}