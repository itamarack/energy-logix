<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
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
        // CORS is handled by Laravel's built-in HandleCors middleware
        // which reads from config/cors.php
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Always render JSON for API routes
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Globally handle formula validation errors as 422 JSON responses
        $exceptions->render(function (\App\Exceptions\ParseException|\App\Exceptions\UndefinedVariableException|\App\Exceptions\CircularDependencyException $e) {
            return response()->json(
                ['message' => $e->getMessage()], 
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        });
    })->create();
