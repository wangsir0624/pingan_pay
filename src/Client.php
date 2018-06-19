<?php
namespace Wangjian\PinganPay;

use GuzzleHttp\Client as Guzzle;
use Wangjian\PinganPay\Util\Aes128EcbCrypt;

/**
 * Class Client
 * @package Wangjian\PinganPay
 *
 * @method array getPayList(array $options)
 * @method array getOrderList(array $options = [])
 * @method array charge(array $options)
 * @method array getOrderInfo(array $options)
 * @method array getOrderStatus(array $options)
 * @method array cancelOrder(array $options)
 * @method array refund(array $options)
 * @method array getBills(array $options = [])
 * @method array getOpenidByAuthCode(array $options)
 */
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

    protected $apiMethods = [
        'getPayList' => 'paylist',
        'getOrderList' => 'order',
        'charge' => 'payorder',
        'getOrderInfo' => 'order/view',
        'getOrderStatus' => 'paystatus',
        'cancelOrder' => 'paycancel',
        'refund' => 'payrefund',
        'getBills' => 'bill/downloadbill',
        'getOpenidByAuthCode' => 'authtoopenid'
    ];

    protected $urlSignWithPrivateKeys = ['paycancel', 'payrefund'];

    protected $openId;

    protected $openKey;

    protected $guzzle;

    protected $crypt;

    protected $optionResolvers = [];

    protected $privateKey = null;

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

    public function setPrivateKey($path)
    {
        if(!file_exists($path)) {
            throw new \InvalidArgumentException('the private key file does\'t exist');
        }

        $this->privateKey = file_get_contents($path);
    }

    public function post($uri, $parameters = [], $headers = [])
    {
        if(in_array($uri, $this->urlSignWithPrivateKeys)) {
            if(empty($this->privateKey)) {
                throw new \RuntimeException('the private key is not set yet');
            }

            if(empty($parameters['sign_type'])) {
                $parameters['sign_type'] = 'RSA';
            }
        }

        $response = $this->guzzle->post($uri, [
            'form_params' => $this->prepareForRequest($parameters),
            'headers' => $headers
        ]);

        $data = json_decode($response->getBody(), true);
        if($data['errcode']) {
            throw new \Exception($data['msg'], $data['errcode']);
        }

        /*if(!$this->verifyResponse($data)) {
            throw new \Exception('响应签名不正确');
        }*/

        return $this->decodeData($data['data']);
    }

    public function __call($name, $arguments)
    {
        if(in_array($name, array_keys($this->apiMethods))) {
            return $this->callApiMethod($name, $arguments);
        }

        throw new \BadMethodCallException("method $name does't exist");
    }

    protected function callApiMethod($name, $arguments)
    {
        $beforeMethod = 'before' . ucfirst($name);
        $afterMethod = 'after' . ucfirst($name);

        if(method_exists($this, $beforeMethod)) {
            call_user_func_array([$this, $beforeMethod], $arguments);
        }
        $result = $this->post($this->apiMethods[$name], $this->createOptionResolver($name)->resolve(isset($arguments[0]) ? $arguments[0] : []));
        if(method_exists($this, $afterMethod)) {
            call_user_func_array([$this, $afterMethod], $arguments);
        }

        return $result;
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
        if(!empty($data['sign_type'])) {
            $newData['sign_type'] = $data['sign_type'];
        }
        $newData['sign'] = $this->calculateSign($newData, empty($data['sign_type']) ? 'common' : $data['sign_type']);

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

    protected function calculateSign($data, $type = 'common')
    {
        $data['open_key'] = $this->openKey;
        ksort($data);
        switch ($type) {
            case 'RSA':
                openssl_sign(http_build_query($data), $sign, openssl_get_privatekey($this->privateKey), OPENSSL_ALGO_SHA1);
                $sign = bin2hex($sign);
                break;
            case 'RSA2':
                openssl_sign(http_build_query($data), $sign, openssl_get_privatekey($this->privateKey), OPENSSL_ALGO_SHA256);
                $sign = bin2hex($sign);
                break;
            default:
                $sign = md5(sha1(http_build_query($data)));
        }
        unset($data['open_key']);

        return $sign;
    }
}