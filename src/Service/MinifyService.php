<?php declare(strict_types=1);

namespace Frosh\HtmlMinify\Service;

use Composer\Autoload\ClassLoader;
use JSMin\JSMin;
use Shopware\Core\Framework\Adapter\Cache\CacheCompressor;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class MinifyService
{
    private string $javascriptPlaceholder = '##SCRIPTPOSITION##';

    private string $spacePlaceholder = '##SPACE##';

    private AdapterInterface $cache;

    public function __construct(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    public function minify(string $content, ?ResponseHeaderBag $headerBag = null): string
    {
        $startTime = microtime(true);
        $lengthInitialContent = mb_strlen($content, 'utf8');

        if ($lengthInitialContent === 0) {
            return '';
        }

        $this->minifySourceTypes($content);

        $javascripts = $this->extractCombinedInlineScripts($content);

        $this->minifyHtml($content);

        //add the minified javascript after minifying html
        $content = str_replace($this->javascriptPlaceholder, '<script>' . $javascripts . '</script>', $content);

        $this->assignCompressionHeader($headerBag, $content, $lengthInitialContent, $startTime);

        return $content;
    }

    private function resetClassLoader(): void
    {
        $file = __DIR__ . '/../../vendor/autoload.php';
        if (!is_file($file)) {
            return;
        }

        $classLoader = require_once $file;

        if ($classLoader instanceof ClassLoader) {
            $classLoader->unregister();
            $classLoader->register(false);
        }
    }

    private function minifyJavascript(string $jsContent): string
    {
        $this->resetClassLoader();

        return (new JSMin($jsContent))->min();
    }

    private function minifyHtml(string &$content): void
    {
        $search = [
            '/(\n|^)(\x20+|\t)/',
            '/(\n|^)\/\/(.*?)(\n|$)/',
            '/\n/',
            '/\<\!--.*?-->/',
            '/(\x20+|\t)/', // Delete multispace (Without \n)
            '/\s+\<label/', // keep whitespace before label tags
            '/span\>\s+/', // keep whitespace after span tags
            '/\s+\<span/', // keep whitespace before span tags
            '/button\>\s+/', // keep whitespace after span tags
            '/\s+\<button/', // keep whitespace before span tags
            '/\>\s+\</', // strip whitespaces between tags
            '/(\"|\')\s+\>/', // strip whitespaces between quotation ("') and end tags
            '/=\s+(\"|\')/', // strip whitespaces between = "'
            '/' . $this->spacePlaceholder . '/', // replace the spacePlaceholder at the end
        ];

        $replace = [
            "\n",
            "\n",
            ' ',
            '',
            ' ',
            $this->spacePlaceholder . '<label',
            'span>' . $this->spacePlaceholder,
            $this->spacePlaceholder . '<span',
            'button>' . $this->spacePlaceholder,
            $this->spacePlaceholder . '<button',
            '><',
            '$1>',
            '=$1',
            ' ',
        ];

        $content = trim(preg_replace($search, $replace, $content));
    }

    private function extractCombinedInlineScripts(string &$content): string
    {
        if (str_contains($content, '</script>') === false) {
            return '';
        }

        $jsContent = '';
        $index = 0;
        $placeholder = $this->javascriptPlaceholder;

        $content = preg_replace_callback('#<script>(.*?)</script>#s', function ($matches) use (&$jsContent, &$index, $placeholder) {
            ++$index;
            $content = trim($matches[1]);

            if (!$this->str_ends_with($content, ';')) {
                $content .= ';';
            }

            $jsContent .= $content . \PHP_EOL;

            return $index === 1 ? $placeholder : '';
        }, $content);

        $cacheItem = $this->cache->getItem(sha1($jsContent));

        if ($cacheItem->isHit()) {
            return CacheCompressor::uncompress($cacheItem);
        }

        $jsContent = $this->minifyJavascript($jsContent);

        $cacheItem = CacheCompressor::compress($cacheItem, $jsContent);
        $cacheItem->expiresAfter(new \DateInterval('P1D'));
        $this->cache->save($cacheItem);

        return $jsContent;
    }

    private function minifySourceTypes(&$content): void
    {
        $search = [
            '/ type=["\']text\/javascript["\']/',
            '/ type=["\']text\/css["\']/',
        ];
        $replace = '';
        $content = preg_replace($search, $replace, $content);
    }

    private function assignCompressionHeader(?ResponseHeaderBag $headerBag, string $content, int $lengthInitialContent, float $startTime): void
    {
        $lengthContent = mb_strlen($content, 'utf8');

        if ($lengthContent === 0) {
            return;
        }

        $savedData = round(100 - 100 / ($lengthInitialContent / $lengthContent), 2);
        $timeTook = (int) ((microtime(true) - $startTime) * 1000);

        if ($headerBag) {
            $headerBag->add(['X-Html-Compressor' => time() . ': ' . $savedData . '% ' . $timeTook . 'ms']);
        }
    }

    private function str_ends_with(string $haystack, string $needle): bool
    {
        return $needle === '' || substr_compare($haystack, $needle, -\strlen($needle)) === 0;
    }
}
