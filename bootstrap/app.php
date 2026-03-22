<?php

declare(strict_types=1);

use App\Exceptions\BusinessRuleViolationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'password',
            'password_confirmation',
        ]);

        $toApiResponse = static function (Request $request, string $message, int $status, array $errors = []): ?Response {
            if (! $request->is('api/*')) {
                return null;
            }

            $payload = ['message' => $message];

            if ($errors !== []) {
                $payload['errors'] = $errors;
            }

            return response()->json($payload, $status);
        };

        $exceptions->render(function (ValidationException $exception, Request $request) use ($toApiResponse): ?Response {
            return $toApiResponse(
                $request,
                'The provided data is invalid.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $exception->errors(),
            );
        });

        $exceptions->render(function (BusinessRuleViolationException $exception, Request $request) use ($toApiResponse): ?Response {
            return $toApiResponse(
                $request,
                $exception->getMessage(),
                $exception->status(),
            );
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($toApiResponse): ?Response {
            return $toApiResponse(
                $request,
                'The requested resource was not found.',
                Response::HTTP_NOT_FOUND,
            );
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) use ($toApiResponse): ?Response {
            $message = $request->route() === null
                ? 'The requested endpoint was not found.'
                : 'The requested resource was not found.';

            return $toApiResponse(
                $request,
                $message,
                Response::HTTP_NOT_FOUND,
            );
        });

        $exceptions->render(function (QueryException $exception, Request $request) use ($toApiResponse): ?Response {
            $sqlState = (string) $exception->getCode();
            $driverCode = (int) ($exception->errorInfo[1] ?? 0);

            if ($sqlState === '23000' && $driverCode === 1062) {
                return $toApiResponse(
                    $request,
                    'The operation could not be completed because the record conflicts with an existing unique value.',
                    Response::HTTP_CONFLICT,
                );
            }

            if ($sqlState === '23000' && in_array($driverCode, [1451, 1452], true)) {
                return $toApiResponse(
                    $request,
                    'The operation could not be completed because it violates a relational integrity constraint.',
                    Response::HTTP_CONFLICT,
                );
            }

            if ($sqlState === 'HY000' && $driverCode === 3819) {
                return $toApiResponse(
                    $request,
                    'The operation could not be completed because it violates a database check constraint.',
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            if ($sqlState !== '23000' && ! ($sqlState === 'HY000' && $driverCode === 3819)) {
                return null;
            }

            return $toApiResponse(
                $request,
                'The operation could not be completed because it violates a database integrity constraint.',
                Response::HTTP_CONFLICT,
            );
        });

        $exceptions->render(function (Throwable $exception, Request $request) use ($toApiResponse): ?Response {
            return $toApiResponse(
                $request,
                'An unexpected error occurred.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        });
    })->create();
