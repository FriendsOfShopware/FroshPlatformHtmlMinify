<?php declare(strict_types=1);

namespace Frosh\HtmlMinify\Service;

use JSMin\JSMin;
use Shopware\Core\Framework\Adapter\Cache\CacheCompressor;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class MinifyService
{
    private string $javascriptPlaceholder = '##SCRIPTPOSITION##';

    private string $spacePlaceholder = '##SPACE##';

    public function __construct(
        private readonly AdapterInterface $cache,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public function minify(string $content, ?ResponseHeaderBag $headerBag = null): string
    {
        $shouldAddCompressionHeader = $this->systemConfigService->getBool('FroshPlatformHtmlMinify.config.addCompressionHeader');

        if ($shouldAddCompressionHeader) {
            $startTime = microtime(true);
            $lengthInitialContent = strlen($content);
        }

        $this->minifySourceTypes($content);

        $minifyJavaScript = $this->systemConfigService->getBool('FroshPlatformHtmlMinify.config.minifyJavaScript');
        $javaScripts = $this->extractCombinedInlineScripts($content, $minifyJavaScript);

        $minifyHTML = $this->systemConfigService->getBool('FroshPlatformHtmlMinify.config.minifyHTML');
        if ($minifyHTML) {
            $this->minifyHtml($content);
        }

        //add the javascript after minifying html
        $content = str_replace($this->javascriptPlaceholder, '<script>' . $javaScripts . '</script>', $content);

        if ($shouldAddCompressionHeader) {
            $this->assignCompressionHeader($headerBag, $content, $lengthInitialContent, $startTime);
        }

        return $content;
    }

    private function minifyJavascript(string $jsContent): string
    {
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

        $replacedContent = preg_replace($search, $replace, $content);
        if ($replacedContent === null) {
            return;
        }

        $content = trim($replacedContent);
    }

    private function extractCombinedInlineScripts(string &$content, bool $minifyJavaScript): string
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

            if (!str_ends_with($content, ';')) {
                $content .= ';';
            }

            $jsContent .= $content . \PHP_EOL;

            return $index === 1 ? $placeholder : '';
        }, $content);

        if (!$minifyJavaScript) {
            return $jsContent;
        }

        $cacheItem = $this->cache->getItem(hash('xxh128', $jsContent));

        if ($cacheItem->isHit()) {
            $uncompressedData = CacheCompressor::uncompress($cacheItem);

            if (\is_string($uncompressedData)) {
                return $uncompressedData;
            }
        }

        $jsContent = $this->minifyJavascript($jsContent);

        $cacheItem = CacheCompressor::compress($cacheItem, $jsContent);
        $cacheItem->expiresAfter(new \DateInterval('P7W'));
        $this->cache->save($cacheItem);

        return $jsContent;
    }

    private function minifySourceTypes(string &$content): void
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
        $lengthContent = strlen($content);

        if ($lengthContent === 0) {
            return;
        }

        $savedData = round(100 - 100 / ($lengthInitialContent / $lengthContent), 2);
        $timeTook = (int) ((microtime(true) - $startTime) * 1000);

        $headerBag?->add(['X-Html-Compressor' => time() . ': ' . $savedData . '% ' . $timeTook . 'ms']);
    }
}
