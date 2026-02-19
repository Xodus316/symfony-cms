<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiLoggingSubscriber implements EventSubscriberInterface
{
    private array $requestStartTimes = [];

    public function __construct(
        private readonly LoggerInterface $apiLogger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Only log API requests
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        // Store request start time
        $requestId = spl_object_hash($request);
        $this->requestStartTimes[$requestId] = microtime(true);

        $this->apiLogger->info('API Request', [
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'request_id' => $requestId,
        ]);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Only log API responses
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $requestId = spl_object_hash($request);
        $duration = isset($this->requestStartTimes[$requestId])
            ? round((microtime(true) - $this->requestStartTimes[$requestId]) * 1000, 2)
            : null;

        $logLevel = $response->getStatusCode() >= 500 ? 'error' : (
            $response->getStatusCode() >= 400 ? 'warning' : 'info'
        );

        $this->apiLogger->{$logLevel}('API Response', [
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'request_id' => $requestId,
        ]);

        // Clean up
        unset($this->requestStartTimes[$requestId]);
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getThrowable();

        // Only log API exceptions
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $requestId = spl_object_hash($request);

        $this->apiLogger->error('API Exception', [
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'request_id' => $requestId,
        ]);

        // Clean up
        unset($this->requestStartTimes[$requestId]);
    }
}
