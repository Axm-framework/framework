<?php

namespace Lang;

use RuntimeException;

/**
 * Class Lang
 * 
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
 * 
 * Interface defining the methods required for a language translator.
 */
interface LangInterface
{
    /**
     * Get the current locale.
     *
     * @return string The current locale.
     */
    public function getLocale(): string;

    /**
     * Translate a key with optional parameters.
     *
     * @param string $key The translation key.
     * @param array $params Optional parameters for string interpolation.
     * @return string The translated message.
     */
    public function trans(string $key, array $params = []): string;
}

/**
 * Class implementing Lang for handling language localization.
 */
class Lang implements LangInterface
{
    private static $instance;
    private array $translations = [];
    private string $locale;
    const DEFAULT_LANGUAGE = 'en';

    /**
     * Private constructor to enforce singleton pattern and load translations.
     */
    private function __construct()
    {
        $this->setLocale();
    }

    /**
     * Get an instance of the Lang class.
     *
     * @return LangInterface An instance of the Lang class.
     */
    public static function make(): LangInterface
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set the current locale and reload translations.
     */
    public function setLocale(): void
    {
        $this->locale = $this->parserLocale(app()->getLocale()) ?? self::DEFAULT_LANGUAGE;

        // Reload translations when changing the language
        $this->loadTranslationsFromFile();
    }

    /**
     * Get the current locale.
     *
     * @return string The current locale.
     */
    public function getLocale(): string
    {
        return $this->locale ?? self::DEFAULT_LANGUAGE;
    }

    /**
     * Translate a key with optional parameters.
     *
     * @param string $key The translation key.
     * @param array $params Optional parameters for string interpolation.
     * @return string The translated message.
     */
    public function trans(string $key, array $params = []): string
    {
        list($file, $messageKey) = explode('.', $key, 2);
        $translationKeyFile = $this->locale . DIRECTORY_SEPARATOR . $file;
        $translationKey = $this->translations[$translationKeyFile] ?? $key;

        $message = $translationKey[$messageKey] ?? '';

        if (!empty($params)) {
            $message = vsprintf($message, $params);
        }

        return $message ?? '';
    }

    /**
     * Load translations from language files.
     *
     * @throws RuntimeException If an error occurs while loading language files.
     */
    public function loadTranslationsFromFile(): void
    {
        $langKey = $this->getLocale();
        $langDir = config('paths.langPath') . DIRECTORY_SEPARATOR . $langKey . DIRECTORY_SEPARATOR;

        $this->translations = [];
        try {
            foreach (glob($langDir . '*.php') as $file) {
                $fileKey = pathinfo($file, PATHINFO_FILENAME);
                $this->translations[$langKey . DIRECTORY_SEPARATOR . $fileKey] = require $file;
            }
        } catch (\Exception $e) {
            throw new RuntimeException("Error loading language file: {$e->getMessage()}");
        }
    }

    /**
     *  Parse the locale string to extract the language and region/country code.
     */
    public function parserLocale(string $locale)
    {
        $locale = explode('_', $locale, 2);
        return $locale[0];
    }
}
