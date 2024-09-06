<?php declare(strict_types=1);

namespace Frosh\HtmlMinify\Listener;

use Frosh\HtmlMinify\Service\MinifyService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseListener
{
    public function __construct(
        private readonly string $environment,
        private readonly MinifyService $minifyService
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($this->environment !== 'prod') {
            return;
        }

        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        if ($response instanceof BinaryFileResponse
            || $response instanceof StreamedResponse) {
            return;
        }

        if ($response->getStatusCode() === Response::HTTP_NO_CONTENT) {
            return;
        }

        $contentType = $response->headers->get('Content-Type', '');
        if (empty($contentType)) {
            return;
        }

        if (!str_contains($contentType, 'text/html')) {
            return;
        }

        $content = $response->getContent();
        if (empty($content)) {
            return;
        }

        $result = $this->minifyService->minify($content, $response->headers);

        $response->setContent($result);
    }
}
