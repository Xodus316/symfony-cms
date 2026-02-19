<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $environment
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only handle JSON responses for API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $data = [
            'error' => 'An error occurred',
            'message' => $exception->getMessage(),
        ];

        // Handle HTTP exceptions
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $data['error'] = Response::$statusTexts[$statusCode] ?? 'Error';
        }

        // Handle validation exceptions
        if ($exception instanceof ValidationFailedException) {
            $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
            $data['error'] = 'Validation Failed';
            $data['violations'] = [];

            foreach ($exception->getViolations() as $violation) {
                $data['violations'][] = [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }
        }

        // Handle authentication exceptions
        if ($exception instanceof AuthenticationException) {
            $statusCode = Response::HTTP_UNAUTHORIZED;
            $data['error'] = 'Authentication Failed';
        }

        // Handle access denied exceptions
        if ($exception instanceof AccessDeniedException) {
            $statusCode = Response::HTTP_FORBIDDEN;
            $data['error'] = 'Access Denied';
            $data['message'] = 'You do not have permission to access this resource';
        }

        // In production, don't expose internal error messages
        if ($this->environment === 'prod' && $statusCode === Response::HTTP_INTERNAL_SERVER_ERROR) {
            $data['message'] = 'Internal server error';
            unset($data['trace']);
        }

        // Add debug information in dev mode
        if ($this->environment === 'dev') {
            $data['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        $response = new JsonResponse($data, $statusCode);
        $event->setResponse($response);
    }
}
