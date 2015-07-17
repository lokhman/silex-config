<?php

namespace Lokhman\Silex\Config;

use Silex\ServiceProviderInterface;
use Silex\Application;

/**
 * Simple and lightweight JSON configuration provider for Silex micro-framework.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-config
 */
class ConfigServiceProvider implements ServiceProviderInterface {

    const ENVVAR = 'SILEX_ENV';
    const ENVDEV = 'dev';
    const CFGEXT = '.json';

    private $env;
    private $params;
    private $dir;

    public function __construct($dir, array $params = [], $env = null) {
        $this->env = $env ? : getenv(self::ENVVAR) ? : self::ENVDEV;
        $this->dir = realpath($dir);
        $this->params = $params + [
            '%dir%' => $this->dir,
            '%env%' => $this->env,
        ];
    }

    private static function load($path) {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException('Unable to load config from ' . $path);
        }
        return file_get_contents($path);
    }

    private static function parse($str) {
        $result = json_decode($str, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON format is invalid');
        }
        return $result;
    }

    private function replace($value) {
        if (!$this->params) {
            return $value;
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->replace($v);
            }
            return $value;
        }
        if (is_string($value)) {
            return strtr($value, $this->params);
        }
        return $value;
    }

    public function register(Application $app) {
        $path = $this->dir . DIRECTORY_SEPARATOR . $this->env . self::CFGEXT;
        foreach (self::parse(self::load($path)) as $key => $value) {
            $app[$key] = $app->share(function() use ($value) {
                return $this->replace($value);
            });
        }
    }

    public function boot(Application $app) {
        /* not implemented */
    }

}
