<?php

use Frosh\HtmlMinify\Service\MinifyService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class MinifyServiceTest extends TestCase
{
    private MinifyService $minifyService;

    protected function setUp(): void
    {
        $this->minifyService = new MinifyService();
    }

    public function testMinify(): void
    {
        $headers = new ResponseHeaderBag();

        self::assertSame('sdqlqfqff aadadad', $this->minifyService->minify('sdqlqfqff
        aadadad', $headers));

        self::assertTrue($headers->has('x-html-compressor'));

        preg_match('/(\d+): (\d+)% (\d+)ms/', $headers->get('x-html-compressor'), $headerParts);
        self::assertCount(4, $headerParts, 'The x-html-compressor-Header is invalid');

        foreach ($headerParts as $key => $part) {
            if ($key === 0) {
                continue;
            }

            self::assertSame($part,(string) (int) $part, 'The x-html-compressor-Header doesn\'t contain numbers');
        }

        $files = glob(__DIR__ . '/MinifyServiceTestFiles/*.source.html');
        static::assertCount(2, $files);

        foreach ($files as $file) {
            $resultFilePath = str_replace('.source.', '.target.', $file);
            if (!file_exists($resultFilePath)) {
                throw new RuntimeException($resultFilePath . ' missing!');
            }

            static::assertStringEqualsFile(
                $resultFilePath,
                $this->minifyService->minify(file_get_contents($file)),
                'Test failed for ' . $file
            );
        }
    }
}
