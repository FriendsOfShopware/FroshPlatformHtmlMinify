<?php

use Frosh\HtmlMinify\Service\MinifyService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class MinifyServiceTest extends TestCase
{
    private MinifyService $minifyService;

    protected function setUp(): void
    {
    }

    /**
     * @dataProvider provider
     */
    public function testMinifyWithCompressionHeader($expectedContent, $content): void
    {
        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->expects(self::once())
            ->method('getBool')
            ->with('FroshPlatformHtmlMinify.config.addCompressionHeader')
            ->willReturn(true);

        $this->minifyService = new MinifyService(
            new ArrayAdapter(),
            $systemConfigService
        );

        $headers = new ResponseHeaderBag();

        self::assertSame($expectedContent, $this->minifyService->minify($content, $headers));

        self::assertTrue($headers->has('x-html-compressor'));

        preg_match('/(\d+): (\d+\d*\.?\d*)% (\d+)ms/', $headers->get('x-html-compressor'), $headerParts);
        self::assertCount(4, $headerParts, 'The x-html-compressor-Header is invalid');

        self::assertSame($headerParts[1],(string) (int) $headerParts[1], 'The x-html-compressor-Header doesn\'t contain valid timestamp');
        self::assertSame($headerParts[2],(string) (float) $headerParts[2], 'The x-html-compressor-Header doesn\'t contain valid percentage');
        self::assertSame($headerParts[3],(string) (int) $headerParts[3], 'The x-html-compressor-Header doesn\'t contain valid runtime');

    }

    /**
     * @dataProvider provider
     */
    public function testMinifyWithoutCompressionHeader($expectedContent, $content): void
    {
        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->expects(self::once())
            ->method('getBool')
            ->with('FroshPlatformHtmlMinify.config.addCompressionHeader')
            ->willReturn(false);

        $this->minifyService = new MinifyService(
            new ArrayAdapter(),
            $systemConfigService
        );

        $headers = new ResponseHeaderBag();

        self::assertSame($expectedContent, $this->minifyService->minify($content, $headers));

        self::assertFalse($headers->has('x-html-compressor'));
    }

    public function provider(): array
    {
        $cases = [];

        $files = glob(__DIR__ . '/MinifyServiceTestFiles/*.source.html');
        foreach ($files as $file) {
            $resultFilePath = str_replace('.source.', '.target.', $file);
            if (!file_exists($resultFilePath)) {
                throw new RuntimeException($resultFilePath . ' missing!');
            }

            $cases[] = [
                file_get_contents($resultFilePath),
                file_get_contents($file)
            ];
        }

        self::assertCount(4, $cases);

        return $cases;
    }
}
