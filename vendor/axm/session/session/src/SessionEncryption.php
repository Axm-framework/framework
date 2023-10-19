<?php
namespace Axm\Encryption;

use RuntimeException;

class Encrypter implements EncrypterInterface, StringEncrypterInterface
{
    /**
     * The encryption key.
     *
     * @var string
     */
    protected $key;

    /**
     * The algorithm used for encryption.
     *
     * @var string
     */
    protected $cipher;

    /**
     * The supported cipher algorithms and their properties.
     *
     * @var array
     */
    private static $supportedCiphers = [
        'aes-128-cbc' => ['size' => 16, 'aead' => false],
        'aes-256-cbc' => ['size' => 32, 'aead' => false],
        'aes-128-gcm' => ['size' => 16, 'aead' => true],
        'aes-256-gcm' => ['size' => 32, 'aead' => true],
    ];

    /**
     * Create a new encrypter instance.
     *
     * @param  string  $key
     * @param  string  $cipher
     * @return void
     *
     * @throws \RuntimeException
     */
    public function __construct($key, $cipher = 'aes-128-cbc')
    {
        $key = (string) $key;

        if (! static::supported($key, $cipher)) {
            $ciphers = implode(', ', array_keys(self::$supportedCiphers));

            throw new RuntimeException("Unsupported cipher or incorrect key length. Supported ciphers are: {$ciphers}.");
        }

        $this->key = $key;
        $this->cipher = $cipher;
    }

    /**
     * Determine if the given key and cipher combination is valid.
     *
     * @param  string  $key
     * @param  string  $cipher
     * @return bool
     */
    public static function supported($key, $cipher)
    {
        if (! isset(self::$supportedCiphers[strtolower($cipher)])) {
            return false;
        }

        return mb_strlen($key, '8bit') === self::$supportedCiphers[strtolower($cipher)]['size'];
    }

    /**
     * Create a new encryption key for the given cipher.
     *
     * @param  string  $cipher
     * @return string
     */
    public static function generateKey($cipher)
    {
        return random_bytes(self::$supportedCiphers[strtolower($cipher)]['size'] ?? 32);
    }

    public function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
      }
    
      public function decrypt($data) {
        $data = base64_decode($data);
        $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
        return openssl_decrypt($encrypted, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv);
      }
    }