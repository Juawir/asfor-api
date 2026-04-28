<?php

namespace App\Http\Traits;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse($data, $message = 'Success', $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function errorResponse($message, $code, $details = null): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $details,
        ], $code);
    }
}
