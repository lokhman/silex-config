<?php
/**
 * Tools for Silex 2+ framework.
 *
 * @author Alexander Lokhman <alex.lokhman@gmail.com>
 *
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

namespace Lokhman\Silex\Tests\Provider;

use Lokhman\Silex\Provider\ConfigServiceProvider;
use PHPUnit\Framework\TestCase;
use Silex\Application;

class ConfigServiceProviderTest extends TestCase
{
    public function setUp()
    {
        putenv('SILEX_ENV');
        putenv('SILEX_ENV_TEST');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testNoOptionsInitializer()
    {
        $app = new Application();
        $app->register(new ConfigServiceProvider());
        $app->boot();
    }

    /**
     * @expectedException RuntimeException
     */
    public function testWrongConfigPath()
    {
        $app = new Application();
        $app->register(new ConfigServiceProvider(), [
            'config.dir' => __DIR__.'/wrong/path',
        ]);
        $app->boot();
    }

    /**
     * @expectedException RuntimeException
     */
    public function testBrokenConfigFile()
    {
        $app = new Application();
        $app->register(new ConfigServiceProvider(), [
            'config.dir' => __DIR__.'/../../config',
            'config.env' => '_broken',
        ]);
        $app->boot();
    }

    public function testDefaultEnvironment()
    {
        $custom = new \stdClass();

        $app = new Application();
        $app->register(new ConfigServiceProvider(), [
            'config.dir' => __DIR__.'/../../config',
            '_custom'    => $custom,
        ]);
        $app->boot();

        $this->assertArrayHasKey('config', $app);
        $this->assertEquals('local', $app['env']);
        $this->assertEquals('%__ENV__%', $app['config']['env']);
        $this->assertSame($custom, $app['_custom']);

        $dir = realpath($app['dir']);
        $this->assertNotSame(false, $dir);
        $this->assertEquals(realpath(__DIR__.'/../'), $dir);
    }

    public function testCustomEnvironment()
    {
        $dir = __DIR__.'/../../config';

        $app = new Application();
        $app->register(new ConfigServiceProvider(), [
            'config.dir' => $dir,
            'config.env' => 'test',
        ]);
        $app->boot();

        $this->assertEquals('test', $app['env']);
        $this->assertEquals(__DIR__, realpath($app['dir']));
        $this->assertEquals(realpath($dir).'/test.json', $app['file']);
        $this->assertContains('<alex.lokhman@gmail.com>', $app['author']);
    }

    public function testCustomDefaultEnvironment()
    {
        $app = new Application();
        $app->register(new ConfigServiceProvider(), [
            'config.dir'         => __DIR__.'/../../config',
            'config.env.default' => 'test',
        ]);
        $app->boot();

        $this->assertEquals('test', $app['env']);
    }

    public function testDefaultEnvVarInitializer()
    {
        putenv('SILEX_ENV=test');

        $app = new Application();
        $app->register(new ConfigServiceProvider(), [
            'config.dir' => __DIR__.'/../../config',
        ]);
        $app->boot();

        $this->assertEquals('test', $app['env']);

        putenv('SILEX_ENV');
    }

    public function testCustomEnvVarInitializer()
    {
        putenv('SILEX_ENV_TEST=test');

        $app = new Application();
        $app->register(new ConfigServiceProvider(), [
            'config.dir'             => __DIR__.'/../../config',
            'config.varname.default' => 'SILEX_ENV_TEST',
        ]);
        $app->boot();

        $this->assertEquals('test', $app['env']);

        putenv('SILEX_ENV_TEST');
    }
}
