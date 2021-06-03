# HTML-Minifer for Shopware 6

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

This plugin delivers minified HTML and minified inline JavaScript to visitors.  
In tests this results in up to 50% smaller contents, with enabled compression up to 30%.  
You can track the compression in Devtools by viewing the header `X-Html-Compressor`.

## Install

- Install via Shopware Store
- Install via manual download
- Install via composer

## Install via manual download

Download the plugin from the release page, copy it to `custom/static-plugins/` and enable it in Shopware.

```bash
composer config repositories.FroshPlatformHtmlMinify vcs git@github.com:FriendsOfShopware/FroshPlatformHtmlMinify.git
composer require frosh/platform-html-minify
bin/console plugin:refresh
bin/console plugin:install --activate FroshPlatformHtmlMinify
```

## Install via composer

Using the following commands to install via composer:

```bash
bin/console plugin:refresh
bin/console plugin:install --activate FroshPlatformHtmlMinify
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## [Buy me a coffee](https://www.paypal.me/tinect/)
