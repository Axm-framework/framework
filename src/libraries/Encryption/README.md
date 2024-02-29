<p align="center">
    <a href="https://packagist.org/packages/axm/encryption">
        <img src="https://poser.pugx.org/axm/encryption/d/total.svg" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/axm/encryption">
        <img src="https://poser.pugx.org/axm/encryption/v/stable.svg" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/axm/encryption">
        <img src="https://poser.pugx.org/axm/encryption/license.svg" alt="License">
    </a>
</p>

Axm Encrypter
This library provides a simple and secure way to encrypt and decrypt data using the AES-256-CBC cipher. It also includes integrity checking to ensure that the data has not been tampered with.

Installation

```bash
composer require axm/encryption
```

## Usage

Example:

```php
use Encryption\Encrypter;

$encrypter = new Encrypter('your-encryption-key');
$encryptedData = $encrypter->encrypt('This is some sensitive data');

$decryptedData = $encrypter->decrypt($encryptedData);
echo $decryptedData;

```

Example:

```php
// Encrypting a string
$encryptedString = $encrypter->encrypt('My secret message');

// Encrypting an array
$data = ['username' => 'johndoe', 'password' => 'secretpassword'];
$encryptedData = $encrypter->encrypt(serialize($data));

// Decrypting data
$decryptedData = $encrypter->decrypt($encryptedData);
$data = unserialize($decryptedData);
echo $data['username'];

```

## Security Considerations

- It is important to keep your encryption key secret. If someone else has access to your encryption key, they will be able to decrypt your data.
- You should also make sure that your encryption key is long enough. A 32-character key is recommended.

## License

<a name="license"></a>

Raxm is open-sourced software licensed under the [MIT license](LICENSE).

