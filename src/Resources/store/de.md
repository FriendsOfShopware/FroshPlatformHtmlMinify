Dieses Plugin bietet Besuchern minimiertes HTML und minimiertes und kombiniertes Inline-JavaScript.  
In Tests führt dies zu bis zu 50% kleineren Inhalten und bei einer gzip-Komprimierung von bis zu 30%.  
Das komprimierte Ergebnis wird fertig im HTTP-Cache abgelegt.  
Sie können die Komprimierung in den Devtools verfolgen, indem Sie den Header "X-Html-Compressor" prüfen.

**Hinweis:** Wenn Ihr Shop bereits einen hohen TTFB (Time To First Byte) hat, lohnt sich dieses Plugin nicht. Es löst einen hohen TTFB nicht und die Minimierung benötigt obendrein etwas zusätzliche Verarbeitungszeit. Die Ursache des hohen TTFB zu beheben bringt deutlich mehr als das Minimieren des HTML.

Dieses Plugin wird von [@FriendsOfShopware](https://store.shopware.com/friends-of-shopware.html) entwickelt.  
Maintainer dieses Plugins ist: [Sebastian König (tinect)](https://github.com/tinect)

Bei Fragen / Fehlern bitte ein [Github Issue](https://github.com/FriendsOfShopware/FroshPlatformHtmlMinify/issues/new) erstellen
