<?php
namespace Wangjian\PinganPay\Util;

class Aes128EcbCrypt
{
    protected $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    public function encrypt($raw) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = $this->pkcs5Pad($raw, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $this->key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return $data;
    }

    public function decrypt($cipher) {
        $decrypted= mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128,
            $this->key,
            $cipher,
            MCRYPT_MODE_ECB
        );

        $decryptedLength = strlen($decrypted);
        $padding = ord($decrypted[$decryptedLength-1]);

        return substr($decrypted, 0, -$padding);
    }

    protected function pkcs5Pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
}