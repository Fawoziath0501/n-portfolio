<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\LabelController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

/*
| API JSON consommée par les deux applications Vue (site public + administration).
| Elle passe par le groupe « web » : session, cookies et protection CSRF (axios).
*/
Route::prefix('api')->group(function () {
    Route::get('site', [SiteController::class, 'data']);
    Route::post('messages', [InteractionController::class, 'message'])->middleware('throttle:6,1');
    Route::post('subscribers', [InteractionController::class, 'subscribe'])->middleware('throttle:6,1');
    Route::post('track', [InteractionController::class, 'track'])->middleware('throttle:120,1');

    Route::prefix('admin')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

        Route::middleware('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('data', [ContentController::class, 'index']);
            Route::put('documents/{key}', [ContentController::class, 'document']);
            Route::put('labels', [LabelController::class, 'update']);

            Route::patch('messages/{message}', [InboxController::class, 'updateMessage']);
            Route::delete('messages/{message}', [InboxController::class, 'destroyMessage']);
            Route::delete('subscribers/{subscriber}', [InboxController::class, 'destroySubscriber']);

            Route::post('media', [MediaController::class, 'upload']);
            Route::post('media/url', [MediaController::class, 'addUrl']);
            Route::delete('media/{media}', [MediaController::class, 'destroy']);

            Route::get('events', [StatsController::class, 'events']);
            Route::delete('events', [StatsController::class, 'clear']);

            Route::get('trash', [TrashController::class, 'index']);
            Route::delete('trash', [TrashController::class, 'empty']);
            Route::post('trash/{type}/{id}/restore', [TrashController::class, 'restore'])->whereNumber('id');
            Route::delete('trash/{type}/{id}', [TrashController::class, 'destroy'])->whereNumber('id');

            Route::get('export', [BackupController::class, 'export']);
            Route::post('import', [BackupController::class, 'import']);

            Route::post('{collection}/reorder', [ContentController::class, 'reorder']);
            Route::post('{collection}', [ContentController::class, 'store']);
            Route::put('{collection}/{id}', [ContentController::class, 'update'])->whereNumber('id');
            Route::delete('{collection}/{id}', [ContentController::class, 'destroy'])->whereNumber('id');
        });
    });

    Route::any('{any}', fn () => response()->json(['message' => 'Not found'], 404))->where('any', '.*');
});

Route::get('sitemap.xml', [SiteController::class, 'sitemap']);
Route::get('robots.txt', [SiteController::class, 'robots']);

// Applications Vue (routage côté client en mode « history »).
Route::get('/admin/{any?}', fn () => view('admin', ['owner' => \App\Models\Profile::query()->first(['first_name', 'last_name'])]))->where('any', '.*');
Route::get('/{any?}', [SiteController::class, 'show'])->where('any', '^(?!storage/).*$');
