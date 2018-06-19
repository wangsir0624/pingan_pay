<?php
namespace Wangjian\PinganPay\OptionResolver;

use Symfony\Component\OptionsResolver\Options;

class GetPayListOptionResolver extends AbstractOptionResolver
{
    protected function configureOptions()
    {
        $this->resolver->setRequired('pmt_type');

        $this->resolver->setAllowedTypes('pmt_type', ['string', 'array']);

        $this->resolver->setNormalizer('pmt_type', function (Options $options, $value) {
            return is_array($value) ? implode(',', $value) : $value;
        });
    }
}