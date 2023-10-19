<?php

namespace Axm\Cache\Drivers;

use Axm\Cache\Cache;
use Axm\Exception\AxmException;

class FileCache extends Cache
{
    public $cachePath;
    public $cachePathPermission = 0777;
    public $cacheFileSuffix     = '.bin';
    public $cacheFilePermission = 0666;
    public $directoryLevels     = 0;
    public $embedExpiry         = false;
    public $expire              = 31536000;        // 1 year
    private $_gcProbability     = 100;
    private $_gced              = false;


    public function init()
    {
        if ($this->cachePath === null) {
            $this->cachePath = STORAGE_PATH . '/framework/cache/views/';

            if (!is_dir($this->cachePath)) {
                mkdir($this->cachePath, $this->cachePathPermission, true);
            } else if (!is_writable($this->cachePath)) {
                throw new AxmException('Cache path is not Temp.');
            }
        }
    }

    public function flush()
    {
        $this->gc(false);
        return true;
    }

    public function get($key)
    {
        $cacheFile = $this->getCacheFile($key);
        if (file_exists($cacheFile)) {

            clearstatcache();
            if (!$this->isExpired($cacheFile)) {
                return $this->embedExpiry ? substr(file_get_contents($cacheFile), 10) : file_get_contents($cacheFile);
            }

            $this->delete($key);
        }

        return false;
    }

    public function set($key, $value, $expire = 0)
    {
        $cacheFile = $this->getCacheFile($key);
        if ($expire <= 0) {
            $expire = 31536000; // 1 year
        }

        $data = $this->embedExpiry ? time() + $expire . "\n" . $value : $value;
        if (@file_put_contents($cacheFile, $data, LOCK_EX) !== false) {
            @chmod($cacheFile, $this->cacheFilePermission);

            return true;
        }

        return false;
    }

    public function delete($key)
    {
        $cacheFile = $this->getCacheFile($key);
        return @unlink($cacheFile);
    }

    public function getCacheFile($key)
    {
        $base = $this->cachePath . DIRECTORY_SEPARATOR . md5($key);
        if ($this->directoryLevels > 0) {
            $hash = md5($key);

            for ($i = 0; $i < $this->directoryLevels; ++$i) {
                $base .= DIRECTORY_SEPARATOR . substr($hash, $i + 1, 2);
            }
        }

        return $base . $this->cacheFileSuffix;
    }

    protected function gc($expiredOnly = true)
    {

        if ($this->_gced || mt_rand(0, 1000000) >= $this->getGCProbability()) {
            return;
        }

        $this->_gced = true;
        foreach (glob($this->cachePath . DIRECTORY_SEPARATOR . '*' . $this->cacheFileSuffix) as $file) {

            if ($expiredOnly && $this->isExpired($file)) {
                @unlink($file);
            } elseif (!$expiredOnly) {
                @unlink($file);
            }
        }

        $this->_gced = false;
    }

    public function getGCProbability()
    {
        return $this->_gcProbability;
    }

    public function setGCProbability($value)
    {
        $value = (int) $value;
        if ($value < 0) {
            $value = 0;
        }

        if ($value > 1000000) {
            $value = 1000000;
        }

        $this->_gcProbability = $value;
    }

    protected function isExpired($cacheFile)
    {
        if ($this->embedExpiry) {

            $data = file_get_contents($cacheFile);
            $timestamp = (int) substr($data, 0, 10);

            if ($timestamp > 0 && time() > $timestamp + (int) substr($data, 10)) {
                return true;
            }
        } else {

            clearstatcache();
            if (filemtime($cacheFile) + $this->getExpire() < time()) {
                return true;
            }
        }

        return false;
    }

    public function getExpire()
    {
        return $this->expire;
    }
}
