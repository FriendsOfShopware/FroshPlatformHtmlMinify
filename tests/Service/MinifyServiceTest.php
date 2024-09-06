<?php

use Frosh\HtmlMinify\Service\MinifyService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class MinifyServiceTest extends TestCase
{
    private MinifyService $minifyService;

    protected function setUp(): void
    {
    }

    /**
     * @dataProvider minifyProvider
     */
    public function testMinify(string $expected, string $source): void
    {
        $systemConfigService = new StaticSystemConfigService();
        $systemConfigService->set('FroshPlatformHtmlMinify.config.minifyJavaScript', true);
        $systemConfigService->set('FroshPlatformHtmlMinify.config.minifyHTML', true);

        $this->minifyService = new MinifyService(
            new ArrayAdapter(),
            $systemConfigService
        );

        $headers = new ResponseHeaderBag();
        $result = $this->minifyService->minify($source, $headers);

        self::assertFalse($headers->has('x-html-compressor'));

        self::assertSame($expected, $result);
    }

    public function testMinifyJavaScriptWithoutHTML(): void
    {
        $systemConfigService = new StaticSystemConfigService();
        $systemConfigService->set('FroshPlatformHtmlMinify.config.minifyJavaScript', true);
        $systemConfigService->set('FroshPlatformHtmlMinify.config.minifyHTML', false);

        $this->minifyService = new MinifyService(
            new ArrayAdapter(),
            $systemConfigService
        );

        $source = '<html><div>   </div><script>var a = 1; var b = 2;</script><script>var c = 1; var d = 2;</script></html>';
        $expected = '<html><div>   </div><script>var a=1;var b=2;var c=1;var d=2;</script></html>';

        $headers = new ResponseHeaderBag();
        $result = $this->minifyService->minify($source, $headers);

        self::assertFalse($headers->has('x-html-compressor'));

        self::assertSame($expected, $result);
    }

    public function testMinifyHTMLWithoutJavaScript(): void
    {
        $systemConfigService = new StaticSystemConfigService();
        $systemConfigService->set('FroshPlatformHtmlMinify.config.minifyJavaScript', false);
        $systemConfigService->set('FroshPlatformHtmlMinify.config.minifyHTML', true);

        $this->minifyService = new MinifyService(
            new ArrayAdapter(),
            $systemConfigService
        );

        $source = '<html><div>   </div><script>var a = 1; var b = 2;</script><script>var c = 1; var d = 2;</script></html>';
        $expected = '<html><div></div><script>var a = 1; var b = 2;</script><script>var c = 1; var d = 2;</script></html>';

        $headers = new ResponseHeaderBag();
        $result = $this->minifyService->minify($source, $headers);

        self::assertFalse($headers->has('x-html-compressor'));

        self::assertSame($expected, $result);
    }

    public function testMinifyWithCompressionHeader(): void
    {
        $systemConfigService = new StaticSystemConfigService();
        $systemConfigService->set('FroshPlatformHtmlMinify.config.addCompressionHeader', true);
        $systemConfigService->set('FroshPlatformHtmlMinify.config.minifyJavaScript', true);
        $systemConfigService->set('FroshPlatformHtmlMinify.config.minifyHTML', true);

        $this->minifyService = new MinifyService(
            new ArrayAdapter(),
            $systemConfigService
        );

        $headers = new ResponseHeaderBag();

        $this->minifyService->minify('<html> </html>', $headers);

        self::assertTrue($headers->has('x-html-compressor'));

        preg_match('/(\d+): (\d+\d*\.?\d*)% (\d+)ms/', $headers->get('x-html-compressor'), $headerParts);
        self::assertCount(4, $headerParts, 'The x-html-compressor-Header is invalid');

        self::assertSame($headerParts[1],(string) (int) $headerParts[1], 'The x-html-compressor-Header doesn\'t contain valid timestamp');
        self::assertSame($headerParts[2],(string) (float) $headerParts[2], 'The x-html-compressor-Header doesn\'t contain valid percentage');
        self::assertSame($headerParts[3],(string) (int) $headerParts[3], 'The x-html-compressor-Header doesn\'t contain valid runtime');
    }

    public function testMinifyWithoutCompressionHeader(): void
    {
        $systemConfigService = new StaticSystemConfigService();
        $systemConfigService->set('FroshPlatformHtmlMinify.config.addCompressionHeader', false);
        $systemConfigService->set('FroshPlatformHtmlMinify.config.minifyJavaScript', true);
        $systemConfigService->set('FroshPlatformHtmlMinify.config.minifyHTML', true);

        $this->minifyService = new MinifyService(
            new ArrayAdapter(),
            $systemConfigService
        );

        $headers = new ResponseHeaderBag();
        $this->minifyService->minify('<html> </html>', $headers);

        self::assertFalse($headers->has('x-html-compressor'));
    }

    public static function minifyProvider(): array
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
