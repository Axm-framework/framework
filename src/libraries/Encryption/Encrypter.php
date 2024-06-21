<?php

namespace Encryption;

use RuntimeException;

class Encrypter
{
    private $key;
    private $cipher = 'AES-256-CBC';
    private $lengthKey = 32;

    public function __construct(string $encryption_key = null, int $keyLength = 32)
    {
        $this->key = $encryption_key ?? $this->generateKeyRandom($keyLength);

        if (mb_strlen($this->key, '8bit') !== $this->lengthKey) {
            throw new RuntimeException("The encryption key must be {$this->lengthKey} characters long.");
        }

        return $this;
    }

    /**
     * Get Key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Generate Key
     */
    public function newKey(int $keyLength = 32)
    {
        $this->key = $this->generateKeyRandom($keyLength);
        return $this;
    }

    /**
     * Encrypt data.
     */
    public function encrypt(string $data): string
    {
        $iv  = random_bytes(16);
        $encryptedData = openssl_encrypt(
            serialize($data),
            $this->cipher,
            $this->key,
            0,
            $iv
        );

        if ($encryptedData === false) {
            throw new RuntimeException('Could not encrypt the data.');
        }

        $payload =  [
            'iv'   => base64_encode($iv),
            'data' => base64_encode($encryptedData),
            'hash' => $this->generateHash($iv),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Could not encrypt the data.');
        }

        return base64_encode($json);
    }

    /**
     * Decrypt data.
     */
    public function decrypt(string $encrypted_payload)
    {
        $payload = json_decode(base64_decode($encrypted_payload), true);

        $iv  = base64_decode($payload['iv']);
        $decrypted = openssl_decrypt(
            base64_decode($payload['data']),
            $this->cipher,
            $this->key,
            0,
            $iv
        );

        if ($decrypted === false) {
            throw new RuntimeException('Could not decrypt the data.');
        }

        if (!hash_equals($this->generateHash($iv), $payload['hash'])) {
            throw new RuntimeException('Invalid payload hash. Possible tampering.');
        }

        return unserialize($decrypted);
    }

    /**
     * Generate a hash based on the IV and encryption key.
     */
    protected function generateHash(string $iv): string
    {
        return hash_hmac('sha256', $iv, $this->key);
    }

    /**
     * Generate a random encryption key.
     */
    public function generateKeyRandom(int $length): string
    {
        $key = random_bytes($length);
        if (mb_strlen($key, '8bit') !== $length) {
            throw new RuntimeException('The encryption key could not be generated.');
        }

        return $key;
    }
}
