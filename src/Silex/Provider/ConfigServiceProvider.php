<?php
/**
 * Tools for Silex 2+ framework.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-tools
 *
 * Copyright (c) 2016 Alexander Lokhman <alex.lokhman@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Lokhman\Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;

/**
 * Silex service provider for lightweight framework configuration.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 * @link https://github.com/lokhman/silex-tools
 */
class ConfigServiceProvider implements ServiceProviderInterface, BootableProviderInterface {

    /**
     * Replaces tokens in the configuration.
     *
     * @param mixed $data   Configuration
     * @param array $tokens Tokens to replace
     *
     * @return mixed
     */
    public static function replaceTokens($data, $tokens) {
        if (is_string($data)) {
            return preg_replace_callback('/%(\w+)%/', function($matches) use ($tokens) {
                $token = strtoupper($matches[1]);
                if (isset($tokens[$token])) {
                    return $tokens[$token];
                }
                return getenv($token) ? : $matches[0];
            }, $data);
        }

        if (is_array($data)) {
            array_walk($data, function(&$value) use ($tokens) {
                $value = static::replaceTokens($value, $tokens);
            });
        }

        return $data;
    }

    /**
     * Reads configuration file.
     *
     * @param string $dir  Configuration directory
     * @param string $path Configuration file path
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function readFile($dir, $path) {
        if (!pathinfo($path, PATHINFO_EXTENSION)) {
            $path .= '.json';
        }

        if ($path[0] != '/') {
            $path = $dir . DIRECTORY_SEPARATOR . $path;
        }

        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException(sprintf('Unable to load configuration from "%s".', $path));
        }

        $data = json_decode(file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Configuration JSON format is invalid.');
        }

        if (isset($data['$extends']) && is_string($data['$extends'])) {
            $extends = static::readFile($dir, $data['$extends']);
            $data = array_replace_recursive($extends, $data);
            unset($data['$extends']);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Container $app) {
        $app['config.dir'] = null;
        $app['config.params'] = [];

        $app['config.env.default'] = 'local';
        $app['config.varname.default'] = 'SILEX_ENV';

        $app['config'] = function(Container $app) {
            if (false === $app['config.dir'] = realpath($app['config.dir'])) {
                throw new \RuntimeException('Parameter "config.dir" should contain a valid path.');
            }

            if (!isset($app['config.env'])) {
                $varname = $app['config.varname.default'];
                if (isset($app['config.varname'])) {
                    $varname = $app['config.varname'];
                }
                $app['config.env'] = getenv($varname) ? : $app['config.env.default'];
            }

            $data = static::readFile($app['config.dir'], $app['config.env']);
            if (isset($data['$params']) && is_array($data['$params'])) {
                $app['config.params'] += $data['$params'];
                unset($data['$params']);
            }

            $params = ['__DIR__' => $app['config.dir'], '__ENV__' => $app['config.env']];
            $params += array_change_key_case($app['config.params'], CASE_UPPER);
            $app['config.params'] = $params;

            return $data;
        };
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app) {
        foreach ($app['config'] as $key => $value) {
            $app[$key] = static::replaceTokens($value, $app['config.params']);
        }
    }

}
