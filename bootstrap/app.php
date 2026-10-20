<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ->middleware('role:admin') / ->middleware('role:superadmin')
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Semua galat di bawah /api/* dijawab sebagai JSON berbentuk
         * { "success": false, "message": "..." } — bentuk yang dipahami
         * frontend lama (api.js / admin.js / main.js).
         */
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // Validasi gagal -> 400 + pesan pertama (aplikasi lama memakai 400, bukan 422).
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->validator->errors()->first(),
                    'errors'  => $e->errors(),
                ], 400);
            }
        });

        // Galat HTTP umum: token CSRF kedaluwarsa (419), terlalu banyak percobaan (429), dst.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $pesan = match ($e->getStatusCode()) {
                404     => 'Endpoint tidak ditemukan.',
                405     => 'Metode tidak diizinkan.',
                419     => 'Sesi halaman sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.',
                429     => 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.',
                default => null,
            };

            if ($pesan === null) {
                return null;
            }

            return response()->json(['success' => false, 'message' => $pesan], $e->getStatusCode(), $e->getHeaders());
        });
    })->create();
