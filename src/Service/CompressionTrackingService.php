<?php declare(strict_types=1);

namespace Frosh\HtmlMinify\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CompressionTrackingService
{
    private bool $isStarted = false;
    private float $startTime = 0;
    private int $initialContentLength = 0;

    public function __construct(
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    private function shouldTrack(): bool
    {
        return $this->systemConfigService->getBool('FroshPlatformHtmlMinify.config.addCompressionHeader');
    }

    public function start(string $content): void
    {
        $this->isStarted = true;

        if ($this->shouldTrack() === false) {
            return;
        }

        $this->startTime = microtime(true);
        $this->initialContentLength = strlen($content);
    }

    public function end(?ResponseHeaderBag $headerBag, string $content): void
    {
        if ($this->isStarted === false) {
            throw new \RuntimeException('You must call start() before end()');
        }

        if ($this->shouldTrack() === false) {
            return;
        }

        $lengthContent = strlen($content);

        if ($lengthContent === 0) {
            return;
        }

        $savedData = round(100 - 100 / ($this->initialContentLength / $lengthContent), 2);
        $timeTook = (int) ((microtime(true) - $this->startTime) * 1000);

        $headerBag?->add(['X-Html-Compressor' => time() . ': ' . $savedData . '% ' . $timeTook . 'ms']);
    }
}
