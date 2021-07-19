<?php

namespace App\Services\GameCrawler;

class Config {
    private $country = '';
    private $config  = [];

    public function __construct(String $country)
    {
        $this->country = strtolower($country);
        $this->config  = $this->loadConfig();
    }

    public function getConfigPath():String
    {
        return __DIR__.'/Config/'.$this->country.'.php';
    }

    public function getConfig(String $keys, $default = null)
    {
        static $config_cache;
        if (!isset($config_cache[$keys])) {
            $value = $this->config;
            foreach (explode('.', $keys) as $key) {
                $value = $value[$key]?? $default;
            }
            $config_cache[$keys] = $value;
        }
        return $config_cache[$keys];
    }


    
    private function loadConfig():Array
    {
        $config_path = $this->getConfigPath();
        return is_file($config_path)? require($config_path) : [];
    }
}