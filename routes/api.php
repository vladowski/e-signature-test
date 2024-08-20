<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SignatureRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return response()->json([
        'user' => $request->user(),
    ]);
})->middleware('auth:sanctum');

Route::prefix('signature-requests')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [SignatureRequestController::class, 'all']);
    Route::get('/{signatureRequest}', [SignatureRequestController::class, 'show']);
    Route::post('/', [SignatureRequestController::class, 'create']);
    Route::post('/{signatureRequest}/sign', [SignatureRequestController::class, 'sign']);
    Route::post('/{signatureRequest}/deny', [SignatureRequestController::class, 'deny']);
    Route::delete('/{signatureRequest}', [SignatureRequestController::class, 'delete']);
});

Route::prefix('documents')->middleware('auth:sanctum')->group(function () {
    Route::post('/upload', [DocumentController::class, 'upload']);
    Route::get('/', [DocumentController::class, 'all']);
    Route::get('/{document}', [DocumentController::class, 'show']);
});
