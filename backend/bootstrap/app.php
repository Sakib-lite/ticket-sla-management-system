<?php

use App\Exceptions\UnprocessableException;
use App\Http\Middleware\AddRequestContext;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Resources\UserResource;
use App\Services\Assignment\TicketAlreadyAssignedException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request) => null);
        $middleware->api(append: [AddRequestContext::class, SecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(fn (UnprocessableException $exception) => response()->json([
            'message' => $exception->getMessage(),
            'errors' => [$exception->field => [$exception->getMessage()]],
        ], 422));

        $exceptions->render(fn (NotFoundHttpException $exception, Request $request) => $request->is('api/*')
            ? response()->json(['message' => 'The requested resource was not found.'], 404)
            : null);

        $exceptions->render(fn (TicketAlreadyAssignedException $exception) => response()->json([
            'message' => $exception->getMessage(),
            'assignee' => UserResource::make($exception->ticket->assignee),
        ], 409));
    })->create();
