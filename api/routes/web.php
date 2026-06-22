<?php

use Illuminate\Support\Facades\Route;

// All application routes are served under /api — this catch-all handles
// any web requests that don't match and returns a clean JSON 404.
Route::fallback(fn () => response()->json(['message' => 'Not Found'], 404));
