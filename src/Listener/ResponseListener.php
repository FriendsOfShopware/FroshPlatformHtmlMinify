<?php declare(strict_types=1);

namespace Frosh\HtmlMinify\Listener;

use Frosh\HtmlMinify\Service\MinifyService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseListener
{
    private string $environment;

    private MinifyService $minifyService;

    public function __construct(string $environment, MinifyService $minifyService)
    {
        $this->environment = $environment;
        $this->minifyService = $minifyService;
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

        if (strpos($response->headers->get('Content-Type', ''), 'text/html') === false) {
            return;
        }

        $result = $this->minifyService->minify($response->getContent(), $response->headers);

        $response->setContent($result);
    }
}
