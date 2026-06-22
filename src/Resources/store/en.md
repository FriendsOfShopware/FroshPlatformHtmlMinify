This plugin delivers minified HTML and minified inline JavaScript to visitors.  
In tests this results in up to 50% smaller contents, with enabled compression up to 30%.  
The compressed result is stored in the HTTP cache.  
You can track the compression in Devtools by viewing the header `X-Html-Compressor`.

**Note:** If your shop already has a high TTFB (Time To First Byte), this plugin is not worth using. It will not solve a high TTFB, and the minification itself adds a little processing time on top. Fixing the root cause of your high TTFB brings far more benefit than minifying the HTML.

This plugin is part of [@FriendsOfShopware](https://store.shopware.com/en/friends-of-shopware.html).  
Maintainer from the plugin is: [Sebastian König (tinect)](https://github.com/tinect)

For questions or bugs please create a [Github Issue](https://github.com/FriendsOfShopware/FroshPlatformHtmlMinify/issues/new)
