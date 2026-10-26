<?php

use App\Http\Controllers\FamilySeederController;
use Illuminate\Support\Facades\Route;

// Authentication-less UI
Route::get('/', [FamilySeederController::class, 'index'])->name('seeder.index');
Route::get('/seeder', [FamilySeederController::class, 'index']);

// Data Operations
Route::post('/api/family/store', [FamilySeederController::class, 'store'])->name('seeder.store');
Route::post('/api/family/batch-seed', [FamilySeederController::class, 'batchSeed'])->name('seeder.batch');
Route::get('/api/family/sample', [FamilySeederController::class, 'getSample'])->name('seeder.sample');
Route::get('/api/family/records', [FamilySeederController::class, 'getRecords'])->name('seeder.records');
Route::delete('/api/family/{id}/{districtCode}', [FamilySeederController::class, 'destroy'])->name('seeder.destroy');

// LGD Master Data Cascading Endpoints
Route::prefix('api/lgd')->group(function () {
    Route::get('/subdistricts/{districtCode}', [FamilySeederController::class, 'apiSubdistricts']);
    Route::get('/ulbs/{districtCode}/{subDistrictCode?}', [FamilySeederController::class, 'apiUlbs']);
    Route::get('/wards/{urbanBodyCode}', [FamilySeederController::class, 'apiWards']);
    Route::get('/blocks/{districtCode}', [FamilySeederController::class, 'apiBlocks']);
    Route::get('/gps/{districtCode}/{blockCode}', [FamilySeederController::class, 'apiGps']);
});

// Aadhaar Validation Endpoint
Route::post('/api/validate/aadhaar', [FamilySeederController::class, 'apiValidateAadhaar']);
