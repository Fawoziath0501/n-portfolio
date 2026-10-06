<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\LabelController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\MailController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\CertificatePreviewController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PostCoverController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

/*
| API JSON consommée par les deux applications Vue (site public + administration).
| Elle passe par le groupe « web » : session, cookies et protection CSRF (axios).
*/
Route::prefix('api')->group(function () {
    Route::get('site', [SiteController::class, 'data']);
    Route::post('messages', [InteractionController::class, 'message'])->middleware('throttle:6,1,messages');
    Route::post('subscribers', [InteractionController::class, 'subscribe'])->middleware('throttle:6,1,subscribers');
    Route::post('track', [InteractionController::class, 'track'])->middleware('throttle:120,1,track');
    Route::post('posts/{slug}/view', [InteractionController::class, 'postView'])->middleware('throttle:30,1,postview');

    Route::prefix('admin')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1,login');

        // auth.session : un changement de mot de passe ferme les autres sessions ouvertes.
        Route::middleware(['auth', 'auth.session'])->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::put('account', [AccountController::class, 'update'])->middleware('throttle:10,1,account');
            Route::get('data', [ContentController::class, 'index']);
            Route::put('documents/{key}', [ContentController::class, 'document']);
            Route::put('labels', [LabelController::class, 'update']);

            Route::patch('messages/{message}', [InboxController::class, 'updateMessage']);
            Route::delete('messages/{message}', [InboxController::class, 'destroyMessage']);
            Route::delete('subscribers/{subscriber}', [InboxController::class, 'destroySubscriber']);
            Route::post('messages/{message}/reply', [MailController::class, 'reply'])->middleware('throttle:20,1,reply');
            Route::post('messages/{message}/whatsapp', [MailController::class, 'whatsapp']);
            Route::put('mail', [MailController::class, 'settings']);
            Route::put('captcha', [MailController::class, 'captcha']);
            Route::post('mail/test', [MailController::class, 'test'])->middleware('throttle:5,1,mailtest');

            Route::post('media', [MediaController::class, 'upload']);
            Route::post('media/url', [MediaController::class, 'addUrl']);
            Route::delete('media/{media}', [MediaController::class, 'destroy']);

            Route::get('events', [StatsController::class, 'events']);
            Route::delete('events', [StatsController::class, 'clear']);

            Route::get('logs', [LogController::class, 'index']);
            Route::get('logs/{file}', [LogController::class, 'show']);
            Route::get('logs/{file}/download', [LogController::class, 'download']);
            Route::delete('logs/{file}', [LogController::class, 'clear']);
            Route::get('trash', [TrashController::class, 'index']);
            Route::delete('trash', [TrashController::class, 'empty']);
            Route::post('trash/{type}/{id}/restore', [TrashController::class, 'restore'])->whereNumber('id');
            Route::delete('trash/{type}/{id}', [TrashController::class, 'destroy'])->whereNumber('id');

            Route::get('export', [BackupController::class, 'export']);
            Route::post('import', [BackupController::class, 'import']);
            Route::post('newsletter/{post}', [NewsletterController::class, 'send'])->middleware('throttle:5,1,newsletter');
            Route::get('backups', [BackupController::class, 'archives']);
            Route::post('backups', [BackupController::class, 'createArchive'])->middleware('throttle:6,1,backup');
            Route::get('backups/{name}', [BackupController::class, 'downloadArchive']);

            Route::post('{collection}/reorder', [ContentController::class, 'reorder']);
            Route::post('{collection}', [ContentController::class, 'store']);
            Route::put('{collection}/{id}', [ContentController::class, 'update'])->whereNumber('id');
            Route::delete('{collection}/{id}', [ContentController::class, 'destroy'])->whereNumber('id');
        });
    });

    Route::any('{any}', fn () => response()->json(['message' => 'Not found'], 404))->where('any', '.*');
});

Route::get('sitemap.xml', [SiteController::class, 'sitemap']);
// Aperçu filigrané d'un certificat (le fichier original n'est jamais exposé).
Route::match(['get', 'post'], 'newsletter/desinscription/{subscriber}', [NewsletterController::class, 'unsubscribe'])->whereNumber('subscriber')->middleware(['signed:relative', 'throttle:30,1,unsubscribe'])->name('newsletter.unsubscribe');
Route::get('blog/{slug}/couverture-{lang}.png', [PostCoverController::class, 'show'])->where('lang', 'fr|en')->middleware('throttle:60,1,postcover');
Route::get('certificats/{certification}/apercu.jpg', [CertificatePreviewController::class, 'show'])->whereNumber('certification')->middleware('throttle:60,1,certpreview');
Route::get('robots.txt', [SiteController::class, 'robots']);

// Applications Vue (routage côté client en mode « history »).
// Administration, à l'adresse ADMIN_PATH (config/portfolio.php).
Route::get('/'.config('portfolio.admin.path').'/{any?}', fn () => view('admin', [
    'owner' => \App\Models\Profile::query()->first(['first_name', 'last_name']),
    'base' => '/'.config('portfolio.admin.path'),
]))->where('any', '.*');
Route::get('/{any?}', [SiteController::class, 'show'])->where('any', '^(?!storage/).*$');
