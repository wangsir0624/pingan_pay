<?php
namespace Wangjian\PinganPay\OptionResolver;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractOptionResolver
{
    protected $resolver;

    public function __construct()
    {
        $this->resolver = new OptionsResolver();
        $this->configureOptions();
    }

    public function resolve(array $options)
    {
        return $this->resolver->resolve($options);
    }

    abstract protected function configureOptions();
}