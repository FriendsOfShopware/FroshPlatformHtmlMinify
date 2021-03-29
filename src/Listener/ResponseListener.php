<?php declare(strict_types=1);

namespace TinectPlatformHtmlMinify\Listener;

use Composer\Autoload\ClassLoader;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use JSMin\JSMin;

class ResponseListener
{
    private $javascriptPlateholder = '##SCRIPTPOSITION##';

    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $start = microtime(true);

        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();

        if ($response instanceof BinaryFileResponse ||
            $response instanceof StreamedResponse) {
            return;
        }

        if (strpos($response->headers->get('Content-Type', ''), 'text/html') === false) {
            return;
        }

        $file = __DIR__.'/../../vendor/autoload.php';
        $classLoader = require_once $file;

        if ($classLoader instanceof ClassLoader) {
            $classLoader->unregister();
            $classLoader->register(false);
        }

        $content = $response->getContent();
        $lengthInitialContent = mb_strlen($content, 'utf8');

        $javascripts = $this->getCombinedInlineScripts($content);

        $this->minifyJavascript($javascripts);
        $this->minifyHtml($content);

        $content = str_replace($this->javascriptPlateholder, '<script>' . $javascripts . '</script>', $content);

        $lengthContent = mb_strlen($content, 'utf8');
        $savedData = round(100 - 100 / ($lengthInitialContent / $lengthContent), 2);
        $timeTook = (int) ((microtime(true) - $start) * 1000);

        $response->headers->add(['X-Tinect-Html-Compressor' => time() . ': ' . $savedData . '% ' . $timeTook. 'ms']);

        $response->setContent($content);
    }

    private function minifyJavascript(string &$content): void {
        $jsMin = new JSMin($content);
        $content = $jsMin->min();
    }

    private function minifyHtml(string &$content): void {
        $search = array(
            '/(\n|^)(\x20+|\t)/',
            '/(\n|^)\/\/(.*?)(\n|$)/',
            '/\n/',
            '/\<\!--.*?-->/',
            '/(\x20+|\t)/', # Delete multispace (Without \n)
            '/\>\s+\</', # strip whitespaces between tags
            '/(\"|\')\s+\>/', # strip whitespaces between quotation ("') and end tags
            '/=\s+(\"|\')/'); # strip whitespaces between = "'

        $replace = array(
            "\n",
            "\n",
            " ",
            "",
            " ",
            "><",
            "$1>",
            "=$1");

        $content = preg_replace($search,$replace,$content);
    }

    private function getCombinedInlineScripts(string &$content): string
    {
        $scriptContents = '';
        $index = 0;
        $placeholder = $this->javascriptPlateholder;
        if (strpos($content, '</script>') !== false) {
            $content = preg_replace_callback('#<script>(.*?)<\/script>#s', static function ($matches) use (&$scriptContents, &$index, $placeholder) {
                $index++;
                $scriptContents .= $matches[1] . PHP_EOL;
                return $index === 1 ? $placeholder : '';
            }, $content);
        }

        return $scriptContents;
    }
}
