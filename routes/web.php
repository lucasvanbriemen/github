<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IsLoggedIn;
use App\Http\Controllers\ImageProxyController;

// To proxy images from private repositories
Route::get('/proxy/image', [ImageProxyController::class, 'proxy'])->name('image.proxy');

// SPA Entry Point - Serve the SPA for all routes
// All routing is handled client-side by Svelte
Route::middleware(IsLoggedIn::class)->group(function () {
    Route::get('/{any?}', function () {
        return view('spa');
    })->where('any', '.*')->name('spa');
});



// use App\Http\Controllers\DashboardController;
// use App\Http\Controllers\IssueController;
// use App\Http\Controllers\PullRequestController;
// use App\Http\Controllers\OrganizationController;
// use App\Http\Controllers\RepositoryController;
// use App\Models\PullRequestReview;
// use App\Mail\PullRequestReviewed;
// use App\GithubConfig;
// use Illuminate\Support\Facades\Mail;

// Route::middleware(IsLoggedIn::class)->group(function () {
//     Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    // Route::get('/webhook_sender', function () {
    //     return view('webhook_sender');
    // })->name('webhook_sender');


//     Route::prefix('organization/{organization}')->group(function () {
//         Route::get('/', [OrganizationController::class, 'show'])->name('organization.show');

//         Route::prefix('{repository}')->group(function () {
//             Route::redirect('/', './tree');

//             Route::get('/tree/{file_path?}', [RepositoryController::class, 'show'])
//                 ->where('file_path', '(.*)?')
//                 ->name('repository.show');

//             Route::get('/issues', [IssueController::class, 'index'])
//                 ->name('repository.issues.index');

//             Route::get('/issues/{issue}', [IssueController::class, 'show'])
//                 ->name('repository.issues.show');

//             Route::get('/prs', [PullRequestController::class, 'index'])
//                 ->name('repository.prs.index');

//             Route::get('/prs/{pull_request}', [PullRequestController::class, 'show'])
//                 ->name('repository.prs.show');

//             Route::get('/prs/{pull_request}/files', [PullRequestController::class, 'showFiles'])
//                 ->name('repository.prs.show.files');
//         });
//     });
// });

// Route::get('/mail_preview/PullRequestReviewed', function () {
//     $pullRequestReview = PullRequestReview::find(3297960164);

//     Mail::to(GithubConfig::USER_EMAIL)
//         ->send(new PullRequestReviewed($pullRequestReview));

//     return response()->json(['status' => 'success']);
    
// })->name('mail_preview');