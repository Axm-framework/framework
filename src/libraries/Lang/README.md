<!-- markdownlint-disable no-inline-html -->
<p align="center">
	<a href="https://packagist.org/packages/axm/Lang"
		><img
			src="https://poser.pugx.org/axm/Lang/v/stable"
			alt="Latest Stable Version"
	/></a>
	<a href="https://packagist.org/packages/axm/Lang"
		><img
			src="https://poser.pugx.org/axm/Lang/downloads"
			alt="Total Downloads"
	/></a>
	<a href="https://packagist.org/packages/axm/Lang"
		><img
			src="https://poser.pugx.org/axm/Lang/license"
			alt="License"
	/></a>
</p>
<br />
<br />


## 📦 Installation

You can also use [Composer](https://getcomposer.org/) to install Axm in your project quickly.

```bash
composer require axm/lang
```

## Lang PHP Library

Lang is a PHP library that provides language localization support. It includes an interface `LangInterface` defining the methods required for a language translator and a class `Lang` implementing this interface for handling language localization.

### LangInterface

 `getLocale(): string`

Returns the current locale.

`trans(string $key, array $params = []): string`

Translates a key with optional parameters.

- `$key`: The translation key.
- `$params`: Optional parameters for string interpolation.

Returns the translated message.

### Lang Class

### Singleton Pattern

The `Lang` class follows the singleton pattern, ensuring only one instance is created throughout the application.

### Methods
`make(): LangInterface`

Static method to get an instance of the `Lang` class.

`setLocale(): void`

Sets the current locale and reloads translations.

`getLocale(): string`

Gets the current locale.

`trans(string $key, array $params = []): string`

Translates a key with optional parameters.

- `$key`: The translation key.
- `$params`: Optional parameters for string interpolation.

Returns the translated message.

`loadTranslationsFromFile(): void`

Loads translations from language files. This method throws an `AxmException` if an error occurs while loading language files.

### Configuration

- `DEFAULT_LANGUAGE`: The default language if no locale is set.

### Usage

```php
// Get an instance of Lang
$lang = Lang::make();

$lang->trans('file.message');

//or

// Translate a key
$lang->trans('file.message', ['param1', 'param2']);
```
