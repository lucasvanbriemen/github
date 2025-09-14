<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\ComponentController;
use App\Http\Middleware\IsLoggedIn;

Route::patch("/organizations", [OrganizationController::class, "updateOrganizations"])->middleware(IsLoggedIn::class)->name("organizations.update");
Route::patch("/organizations/repositories", [RepositoryController::class, "updateRepositories"])->middleware(IsLoggedIn::class)->name("organizations.repositories.update");
Route::get("/organization/{organization}/{repository}/tree/{file_path?}", [RepositoryController::class, "show_file_tree"])
  ->middleware(IsLoggedIn::class)
  ->name("api.repositories.show")
  ->where('file_path', '.*');

Route::prefix('components')->name('components.')->group(function () {
    Route::get('/', [ComponentController::class, 'index'])->name('index');
    Route::get('/{name}', [ComponentController::class, 'show'])->name('show');
    Route::get('/{name}/render', [ComponentController::class, 'render'])->name('render');
    Route::get('/{name}/assets', [ComponentController::class, 'assets'])->name('assets');
    Route::get('/{name}/inline', [ComponentController::class, 'inline'])->name('inline');
    Route::post('/batch', [ComponentController::class, 'batch'])->name('batch');
    Route::post('/refresh/{name?}', [ComponentController::class, 'refresh'])->name('refresh');
    Route::delete('/cache/{name?}', [ComponentController::class, 'clearCache'])->name('cache.clear');
    Route::get('/health/check', [ComponentController::class, 'health'])->name('health');
});