# 2.2.0

* Feat: always combine inline JavaScript, but suppress its minification if config is disabled
* Feat: mark support for Shopware 6.7

# 2.1.0

* Feat: add dedicated options for javascript and html

# 2.0.3

* Feat: add support for Shopware 6.6

# 2.0.2

* Perf: change comparison of string length to byte length for the compression header which is also faster
* Perf: change internal cache key for JavaScript from sha1 to xxh128 hash
* Feat: enable compressionHeader for fresh installations

# 2.0.1

* Feat: disable compressionHeader on default, add related configuration

# 2.0.0

* Shopware 6.5 compatibility

# 1.0.5

* Perf: Add cache for javascript minification

# 1.0.4

* Fix: Fix error with 0B result

# 1.0.3

* Feat: Compression is only performed in production mode
 
# 1.0.2

* Fix: Fix errors caused by empty content

# 1.0.1

* Feat: keep spaces around span tags
* Feat: Keep spaces around button tags
* Feat: Remove standard types `text/javascript` and` text/css`
* Feat: Put semicolons at the end of each Javascript block
* Fix: Correct behavior when installing plug-ins via composer

# 1.0.0

* First release in Store
