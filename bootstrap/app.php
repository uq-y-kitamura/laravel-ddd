<?php

use Domain\Shared\Exception\BusinessRuleViolationException;
use Domain\Shared\Exception\DomainException;
use Domain\Shared\Exception\EntityNotFoundException;
use Domain\Shared\Exception\InvalidValueException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // ドメイン例外を HTTP ステータスへ変換する（ドメイン層は HTTP を知らない）。
        $exceptions->render(function (DomainException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $status = match (true) {
                $e instanceof EntityNotFoundException => Response::HTTP_NOT_FOUND,
                $e instanceof BusinessRuleViolationException => Response::HTTP_CONFLICT,
                $e instanceof InvalidValueException => Response::HTTP_UNPROCESSABLE_ENTITY,
                default => Response::HTTP_BAD_REQUEST,
            };

            return new JsonResponse(['message' => $e->getMessage()], $status);
        });
    })->create();
