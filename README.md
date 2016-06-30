# silex-config
Simple and lightweight JSON configuration provider for [Silex](http://silex.sensiolabs.org/)
micro-framework. It uses JSON file format to manage configuration. Library also supports
different environments via setting global environment variable or passing command line (CLI)
parameter.

Library is fully compatible with **Silex 2.0+** (releases `2.0+`). Legacy versions are
supported in branch `v1` (releases `<2.0`).

## Installation
You can install library through [Composer](http://getcomposer.org):

    composer require lokhman/silex-config

## Usage
### File structure
First off, create a `config` folder in your application root and add configuration JSON files
one per intended environment (default is `dev`):

    /
      bin/
      config/
        dev.json
        uat.json
        prod.json
      src/
      vendor/
      web/
      composer.json
      ...

### Config files
Next, add all your defaults to the config files, e.g.:

    {
      "env": "%env%",
      "debug": true,
      "dbs.options": {
        "local": {
          "driver": "pdo_mysql",
          "host": "localhost",
          "dbname": "database",
          "user": "root",
          "password": "",
          "charset": "utf8"
        }
      },
      "any": {
        "other": "constant"
      }
    }

### Register
Now register service provider in your Silex application:

    use Lokhman\Silex\Config\ConfigServiceProvider;
    
    $app->register(new ConfigServiceProvider(__DIR__ . '/../../config'));

`$dir` parameter refers to a configuration folder path with `.json` files.

### Global environment variable
Finally, you can set up your web server to add support of different deployment environments.
In order to do this, you have to set a global environmental variable.

#### nginx + PHP-FPM

    fastcgi_param SILEX_ENV prod

#### Apache

    SetEnv SILEX_ENV prod

### CLI

Starting from version 2.1, library supports command line `--env` parameter to define environment.

    $ php bin/script.php --env=prod

## Options

`ConfigServiceProvider` constructor supports additional optional parameters as:

- `array $params`: array of replacement tokens to use in configuration, e.g. `['%name%' => 'Alexander']`;
- `string $env`: name of environment to use strictly on provider registration (ignores global environment variable), e.g. `"uat"`.

Example usage:

    $app->register(new ConfigServiceProvider(__DIR__ . '/../../config', [
      '%version%' => '1.2',
      '%root%' => 'root',
    ]), 'uat');

By default, service provider injects tokens `%dir%` and `%env%` to `$params`, which refer to `$dir` and `$env` variable.

## License
Library is available under the MIT license. The included LICENSE file describes this in detail.
