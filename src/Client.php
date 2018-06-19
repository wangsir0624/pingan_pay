<?php
namespace Wangjian\PinganPay;

use GuzzleHttp\Client as Guzzle;
use Wangjian\PinganPay\Util\Aes128EcbCrypt;

class Client
{
    const API_HOST = 'https://api.orangebank.com.cn/mct1/';
    const API_HOST_TEST = 'https://mixpayuat4.orangebank.com.cn/mct1/';

    const ORDER_TYPE_TRADE = 1;
    const ORDER_TYPE_REFUND = 2;

    const ORDER_STATUS_SUCCESS = 1;
    const ORDER_STATUS_PAYING = 2;
    const ORDER_STATUS_CANCELED = 4;
    const ORDER_STATUS_CONFIRMING = 9;

    protected $openId;

    protected $openKey;

    protected $guzzle;

    protected $crypt;

    protected $optionResolvers = [];

    public function __construct($openId, $openKey, $test = false)
    {
        $this->openId = $openId;
        $this->openKey = $openKey;
        $this->guzzle = new Guzzle([
            'base_uri' => $test ? static::API_HOST_TEST : static::API_HOST,
            'verify' => false
        ]);

        $this->crypt = new Aes128EcbCrypt($this->openKey);
    }

    public function getPayList($pmtType)
    {
        if(is_array($pmtType)) {
            $pmtType = implode(',', $pmtType);
        }

        return $this->post('paylist', [
            'pmt_type' => $pmtType
        ]);
    }

    public function getOrderList(array $options = [])
    {
        return $this->post('order', $this->createOptionResolver('getOrderList')->resolve($options));
    }

    public function charge(array $options)
    {
        return $this->post('payorder', $this->createOptionResolver('charge')->resolve($options));
    }

    public function getOrderInfo($outNo)
    {
        return $this->post('order/view', [
            'out_no' => $outNo
        ]);
    }

    public function getOrderStatus(array $options)
    {
        $options = $this->createOptionResolver('getOrderStatus')->resolve($options);
        if(is_null($options['ord_no']) && is_null($options['out_no'])) {
            throw new \InvalidArgumentException('the ord_no and out_no can\'t be empty at the same time');
        }

        return $this->post('paystatus', $options);
    }

    public function post($uri, $parameters = [], $headers = [])
    {
        $response = $this->guzzle->post($uri, [
            'form_params' => $this->prepareForRequest($parameters),
            'headers' => $headers
        ]);

        $data = json_decode($response->getBody(), true);
        if($data['errcode']) {
            throw new \Exception($data['msg'], $data['errcode']);
        }

        if(!$this->verifyResponse($data)) {
            throw new \Exception('响应签名不正确');
        }

        return $this->decodeData($data['data']);
    }

    protected function createOptionResolver($name)
    {
        $resolverName = ucfirst($name) . 'OptionResolver';

        if(!isset($this->optionResolvers[$resolverName])) {
            $className = "\\Wangjian\\PinganPay\\OptionResolver\\$resolverName";
            $this->optionResolvers[$resolverName] = new $className();
        }

        return $this->optionResolvers[$resolverName];
    }

    protected function prepareForRequest(array $data)
    {
        $newData = [];
        $newData['open_id'] = $this->openId;
        $newData['timestamp'] = time();
        $newData['data'] = $this->encodeData($data);
        $newData['sign'] = $this->calculateSign($newData);

        return $newData;
    }

    protected function verifyResponse($data)
    {
        $sign = $data['sign'];
        unset($data['sign']);

        return $sign == $this->calculateSign($data);
    }

    protected function encodeData(array $data)
    {
        return bin2hex($this->crypt->encrypt(json_encode($data)));
    }

    protected function decodeData($cipher)
    {
        return json_decode($this->crypt->decrypt(hex2bin($cipher)), true);
    }

    protected function calculateSign($data)
    {
        $data['open_key'] = $this->openKey;
        ksort($data);
        $sign = md5(sha1(http_build_query($data)));
        unset($data['open_key']);

        return $sign;
    }
}