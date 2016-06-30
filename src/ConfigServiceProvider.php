<?php

namespace Lokhman\Silex\Config;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Simple and lightweight JSON configuration provider for Silex micro-framework.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-config
 */
class ConfigServiceProvider implements ServiceProviderInterface {

    const ENV_VAR_NAME = 'SILEX_ENV';
    const FILE_EXTENSION = '.json';
    const DEFAULT_ENV = 'dev';

    protected $env;
    protected $params;
    protected $dir;

    /**
     * Class constructor.
     *
     * @param string $dir
     * @param array  $params
     * @param string $env
     */
    public function __construct($dir, array $params = [], $env = null) {
        $this->env = self::getenv($env);
        $this->dir = realpath($dir);
        $this->params = $params + [
            '%dir%' => $this->dir,
            '%env%' => $this->env,
        ];
    }

    /**
     * Gets environment name based on $env, $argv, or $_ENV.
     *
     * @param string|null $env
     *
     * @return string
     */
    protected static function getenv($env = null) {
        if ($env !== null) {
            return $env;
        }
        $opts = getopt('', ['env:']);
        if ($opts && isset($opts['env'])) {
            return $opts['env'];
        }
        return getenv(self::ENV_VAR_NAME) ? : self::DEFAULT_ENV;
    }

    /**
     * Loads file contents from the path.
     *
     * @param string $path
     *
     * @throws \RuntimeException
     * @return string
     */
    protected static function load($path) {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException('Unable to load config from ' . $path);
        }
        return file_get_contents($path);
    }

    /**
     * Converts file contents to PHP literal.
     *
     * @param string $str
     *
     * @throws \RuntimeException
     * @return mixed
     */
    protected static function parse($str) {
        $result = json_decode($str, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON format is invalid');
        }
        return $result;
    }

    /**
     * Recursively replaces tokens.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function replace($value) {
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

    /**
     * Registers Config service in the given app.
     *
     * @param \Pimple\Container $app
     */
    public function register(Container $app) {
        $path = $this->dir . DIRECTORY_SEPARATOR . $this->env . self::FILE_EXTENSION;
        foreach (self::parse(self::load($path)) as $key => $value) {
            $app[$key] = $app->factory(function() use ($value) {
                return $this->replace($value);
            });
        }
    }

}
