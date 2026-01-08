<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\WorkspaceController;
use App\Http\Controllers\Api\V1\PlansController;    
use App\Http\Controllers\Api\V1\UsageController;
use App\Http\Controllers\Api\V1\ListsController;
use App\Http\Controllers\Api\V1\ContactsController;
use App\Http\Controllers\Api\V1\TemplatesController;
use App\Http\Controllers\Api\V1\CampaignsController;
use App\Http\Controllers\Api\V1\Public\ClickController;
use App\Http\Controllers\Api\V1\Public\OpenController;
use App\Http\Controllers\Api\V1\Public\MailgunWebhookController;
use App\Http\Controllers\Api\V1\Public\UnsubscribeController;   

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', RegisterController::class);
    Route::post('/auth/login', LoginController::class);

    Route::get('/u/{messageId}/{signature}', UnsubscribeController::class);
    Route::get('/t/o/{messageId}/{signature}.gif', OpenController::class);
    Route::get('/t/c/{messageId}/{hash}', ClickController::class);  
    Route::post('/webhooks/mailgun', MailgunWebhookController::class);

    Route::middleware(['auth:sanctum', 'workspace'])->group(function () {
        Route::post('/auth/logout', LogoutController::class);
        Route::get('/me', MeController::class);
        Route::get('/workspace', WorkspaceController::class);

        Route::get('/plans', PlansController::class);
        Route::get('/usage', UsageController::class);


        Route::get('/contacts', [ContactsController::class, 'index']);
        Route::post('/contacts', [ContactsController::class, 'store']);
        Route::get('/contacts/{id}', [ContactsController::class, 'show']);
        Route::put('/contacts/{id}', [ContactsController::class, 'update']);
        Route::delete('/contacts/{id}', [ContactsController::class, 'destroy']);
        Route::post('/contacts/import-csv', [ContactsController::class, 'importCsv']);

        Route::get('/lists', [ListsController::class, 'index']);
        Route::post('/lists', [ListsController::class, 'store']);
        Route::get('/lists/{id}', [ListsController::class, 'show']);
        Route::put('/lists/{id}', [ListsController::class, 'update']);
        Route::delete('/lists/{id}', [ListsController::class, 'destroy']);
        Route::post('/lists/{id}/contacts', [ListsController::class, 'bulkContacts']);


        // Templates
        Route::get('/templates', [TemplatesController::class, 'index']);
        Route::get('/templates/{id}', [TemplatesController::class, 'show']);
        Route::post('/templates', [TemplatesController::class, 'store']);
        Route::put('/templates/{id}', [TemplatesController::class, 'update']);
        Route::delete('/templates/{id}', [TemplatesController::class, 'destroy']);

        // Campaigns
        Route::get('/campaigns', [CampaignsController::class, 'index']);
        Route::post('/campaigns', [CampaignsController::class, 'store']);
        Route::get('/campaigns/{id}', [CampaignsController::class, 'show']);
        Route::put('/campaigns/{id}', [CampaignsController::class, 'update']);
        Route::post('/campaigns/{id}/audience', [CampaignsController::class, 'audience']);
        Route::post('/campaigns/{id}/preview', [CampaignsController::class, 'preview']);
        Route::post('/campaigns/{id}/schedule', [CampaignsController::class, 'schedule']);

        Route::post('/campaigns/{id}/send-now', [CampaignsController::class, 'sendNow']);
        Route::get('/campaigns/{id}/stats', [CampaignsController::class, 'stats']);

        Route::get('/campaigns/{id}/sends', [CampaignSendsController::class, 'byCampaign']);
        Route::get('/campaign-sends/{id}', [CampaignSendsController::class, 'show']);

        Route::get('/messages', [MessagesController::class, 'index']);
        Route::get('/messages/{id}', [MessagesController::class, 'show']);

    });     
});
