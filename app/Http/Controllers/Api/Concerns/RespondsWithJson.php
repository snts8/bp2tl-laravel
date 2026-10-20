<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;

/**
 * Bentuk respons JSON yang sama dengan API PHP lama:
 *   sukses : { "success": true, ...data }
 *   gagal  : { "success": false, "message": "..." } + kode HTTP
 * Frontend (admin.js, main.js, dst.) bergantung pada bentuk ini.
 */
trait RespondsWithJson
{
    protected function ok(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json(['success' => true] + $data, $status);
    }

    protected function fail(string $message, int $status = 400): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
