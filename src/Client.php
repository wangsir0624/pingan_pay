<?php
namespace Wangjian\PinganPay;

use GuzzleHttp\Client as Guzzle;
use Wangjian\PinganPay\OptionResolver\AbstractOptionResolver;
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
    /**
     * API host constants
     * @const string
     */
    const API_HOST = 'https://api.orangebank.com.cn/mct1/';
    const API_HOST_TEST = 'https://mixpayuat4.orangebank.com.cn/mct1/';

    /**
     * Order type constants
     * @const int
     */
    const ORDER_TYPE_TRADE = 1;
    const ORDER_TYPE_REFUND = 2;

    /**
     * Order status constants
     * @const int
     */
    const ORDER_STATUS_SUCCESS = 1;
    const ORDER_STATUS_PAYING = 2;
    const ORDER_STATUS_CANCELED = 4;
    const ORDER_STATUS_CONFIRMING = 9;

    /**
     * api methods
     * @var array
     */
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

    /**
     * @var array
     */
    protected $urlSignWithPrivateKeys = ['paycancel', 'payrefund'];

    /**
     * @var string
     */
    protected $openId;

    /**
     * @var string
     */
    protected $openKey;

    /**
     * @var Guzzle
     */
    protected $guzzle;

    /**
     * @var Aes128EcbCrypt
     */
    protected $crypt;

    /**
     * cached option resolvers
     * @var array
     */
    protected $optionResolvers = [];

    /**
     * @var string
     */
    protected $privateKey = null;

    /**
     * Client constructor.
     * @param string $openId
     * @param string $openKey
     * @param bool $test
     */
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

    /**
     * set private key
     * @param string $path  the private key file path
     * @throws \InvalidArgumentException when the private key file does'n exist
     */
    public function setPrivateKey($path)
    {
        if(!file_exists($path)) {
            throw new \InvalidArgumentException('the private key file does\'t exist');
        }

        $this->privateKey = file_get_contents($path);
    }

    /**
     * send a post request
     * @param string $uri
     * @param array $parameters
     * @param array $headers
     * @return array
     * @throws \Exception
     */
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

    /**
     * @param string $name
     * @param array $arguments
     * @return array|mixed
     */
    public function __call($name, $arguments)
    {
        if(in_array($name, array_keys($this->apiMethods))) {
            return $this->callApiMethod($name, $arguments);
        }

        throw new \BadMethodCallException("method $name does't exist");
    }

    /**
     * call api method
     * @param string $name
     * @param array $arguments
     * @return array
     */
    protected function callApiMethod($name, $arguments)
    {
        $beforeMethod = 'before' . ucfirst($name);
        $afterMethod = 'after' . ucfirst($name);
        $options = isset($arguments[0]) ? $arguments[0] : [];

        if(method_exists($this, $beforeMethod)) {
            call_user_func_array([$this, $beforeMethod], $arguments);
        }
        $result = $this->post($this->apiMethods[$name], $this->createOptionResolver($name)->resolve($options));
        if(method_exists($this, $afterMethod)) {
            $result = call_user_func_array([$this, $afterMethod], array_merge([$result], $arguments));
        }

        return $result;
    }

    /**
     * create option resolver
     * @param string $name
     * @return AbstractOptionResolver
     */
    protected function createOptionResolver($name)
    {
        $resolverName = ucfirst($name) . 'OptionResolver';

        if(!isset($this->optionResolvers[$resolverName])) {
            $className = "\\Wangjian\\PinganPay\\OptionResolver\\$resolverName";
            $this->optionResolvers[$resolverName] = new $className();
        }

        return $this->optionResolvers[$resolverName];
    }

    /**
     * @param array $data
     * @return array
     */
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

    /**
     * @param array $data
     * @return bool
     */
    protected function verifyResponse($data)
    {
        $sign = $data['sign'];
        unset($data['sign']);

        return $sign == $this->calculateSign($data);
    }

    /**
     * @param array $data
     * @return string
     */
    protected function encodeData(array $data)
    {
        return bin2hex($this->crypt->encrypt(json_encode($data)));
    }

    /**
     * @param string $cipher
     * @return array
     */
    protected function decodeData($cipher)
    {
        return json_decode($this->crypt->decrypt(hex2bin($cipher)), true);
    }

    /**
     * @param array $data
     * @param string $type
     * @return string
     */
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

    protected function beforeGetOrderStatus(array $options)
    {
        if(empty($options['ord_no']) && empty($options['out_no'])) {
            throw new \InvalidArgumentException('the ord_no and out_no parameter can\'t be empty at the same time');
        }
    }

    protected function beforeCancelOrder(array $options)
    {
        if(empty($options['ord_no']) && empty($options['out_no'])) {
            throw new \InvalidArgumentException('the ord_no and out_no parameter can\'t be empty at the same time');
        }
    }
}