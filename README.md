# HTML-Minifer for Shopware 6

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

This plugin delivers minified HTML and minified inline JavaScript to visitors.  
In tests this results in up to 50% smaller contents, with enabled compression up to 30%.  
You can track the compression in Devtools by viewing the header `X-Html-Compressor`.

## Download

### Composer from [packagist.org](https://packagist.org/packages/frosh/platform-html-minify)
```
composer require frosh/platform-html-minify
```

### Store

Download Plugin via plugin manager or extension manager

### ZIP

Download the plugin from the release page, upload it into the plugin manager or extension manager and enable it
Latest release:
```
https://github.com/FriendsOfShopware/FroshPlatformHtmlMinify/releases/latest/download/FroshPlatformHtmlMinify.zip
```

## Install

```bash
bin/console plugin:refresh
bin/console plugin:install -c --activate FroshPlatformHtmlMinify
```


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
