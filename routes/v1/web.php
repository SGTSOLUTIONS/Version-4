<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommissionerController;
use App\Http\Controllers\CorporationController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SurveyorController;
use App\Http\Controllers\TeamleaderController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WardController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PointdataController;
use App\Http\Controllers\TeamManagementController;
use App\Http\Controllers\InfrastructureController;
use App\Http\Controllers\VariationController;

Route::prefix('infrastructure')->group(function () {
    Route::get('/data/{wardId}', [InfrastructureController::class, 'getInfrastructureData']);
    Route::get('/summary/{wardId}', [InfrastructureController::class, 'getInfrastructureSummary']);
    Route::get('/type/{wardId}/{type}', [InfrastructureController::class, 'getFeatureByType']);
});
Route::get('/', function () {
    return view('welcome');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'submitLogin'])->name('login.submit');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'submitRegister'])->name('register.submit');

    Route::get('/forget', [AuthController::class, 'shownForget'])->name('forgetemail');
    Route::post('/forget', [AuthController::class, 'submitForget'])->name('sendForget');

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'submitResetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'profileShow'])->name('profile');
    // Add this inside the auth middleware group
    Route::put('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/save-feature', [FeatureController::class, 'addFeature']);
    Route::post('/polygon-split', [FeatureController::class, 'polygonSplit']);
    Route::post('/update-polygon', [FeatureController::class, 'polygonUpdate']);
    Route::post('/delete-feature', [FeatureController::class, 'polygonDelete']);
    Route::post('/point-data/${id}/qc', [CommissionerController::class, 'qcUpdate']);

    Route::get('/area-variation/{wardId}', [VariationController::class, 'areaVariation'])
        ->name('area.variation');

    Route::get('/usage-variation/{wardId}', [VariationController::class, 'usageVariation'])
        ->name('usage.variation');
    Route::post('/variation/filter', [VariationController::class, 'filterVariations'])->name('variation.filter');
    Route::post('/variation/export', [VariationController::class, 'exportVariations'])->name('variation.export');
    Route::get('/usage-variation/{wardId}', [VariationController::class, 'usageVariation'])->name('variation.usage');
    Route::get('/area-variation/{wardId}', [VariationController::class, 'areaVariation'])->name('variation.area');
});



// ─── Admin Routes ────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Corporation routes
    Route::get('corporations/list', [CorporationController::class, 'list'])->name('corporations.list');
    Route::resource('corporations', CorporationController::class);

    // Zone routes
    Route::get('zones/by-corporation', [ZoneController::class, 'getZonesByCorporation'])->name('zones.byCorporation');
    Route::get('zones/list', [ZoneController::class, 'list'])->name('zone.list');
    Route::resource('zones', ZoneController::class);

    // Ward routes
    Route::get('wards/list', [WardController::class, 'list'])->name('ward.list');
    Route::resource('wards', WardController::class);

    // User routes
    Route::get('users/list', [UserManagementController::class, 'list'])->name('users.list');
    Route::resource('users', UserManagementController::class);

    // Team Management routes
    Route::get('teams', [TeamManagementController::class, 'index'])->name('teams.index');
    Route::get('teams/list', [TeamManagementController::class, 'list'])->name('teams.list');
    Route::get('teams/stats', [TeamManagementController::class, 'getTeamStats'])->name('teams.stats');
    Route::get('teams/export', [TeamManagementController::class, 'exportTeams'])->name('teams.export');
    Route::get('teams/wards-with-teams', [TeamManagementController::class, 'getWardsWithTeams'])->name('teams.wardsWithTeams');
    Route::get('teams/all-with-surveyors', [TeamManagementController::class, 'getAllTeamsWithSurveyors'])->name('teams.allWithSurveyors');
    Route::get('teams/{id}', [TeamManagementController::class, 'show'])->name('teams.show');
    Route::get('teams/{id}/available-surveyors', [TeamManagementController::class, 'getAvailableSurveyors'])->name('teams.availableSurveyors');
    Route::get('teams/{id}/members', [TeamManagementController::class, 'getTeamMembers'])->name('teams.members');
    Route::post('teams/{id}/assign-surveyor', [TeamManagementController::class, 'assignSurveyor'])->name('teams.assignSurveyor');
    Route::post('teams/{id}/bulk-assign', [TeamManagementController::class, 'bulkAssignSurveyors'])->name('teams.bulkAssign');
    Route::post('teams/{id}/remove-surveyor', [TeamManagementController::class, 'removeSurveyor'])->name('teams.removeSurveyor');
    Route::post('teams/{id}/remove-multiple', [TeamManagementController::class, 'removeMultipleSurveyors'])->name('teams.removeMultiple');
    Route::delete('teams/{id}', [TeamManagementController::class, 'destroy'])->name('teams.destroy');
});
// Add this inside your routes file
Route::get('/api/corporation/{id}/boundaries', function ($id) {
    $corporation = App\Models\Corporation::find($id);
    if (!$corporation) {
        return response()->json(['error' => 'Corporation not found'], 404);
    }

    // Get all wards for this corporation with their boundaries
    $wards = App\Models\Ward::where('corporation_id', $id)->get();
    $boundaries = [];

    foreach ($wards as $ward) {
        // Check if ward has boundary data
        if (isset($ward->boundary) && !empty($ward->boundary)) {
            $boundaries[] = $ward->boundary;
        }
    }

    return response()->json(['boundaries' => $boundaries]);
})->name('api.corporation.boundaries');
// ─── Commissioner ─────────────────────────────────────────
Route::middleware(['auth', 'role:commissioner,dc,ac,aro,bc'])->prefix('commissioner')->name('commissioner.')->group(function () {
    Route::get('/dashboard', [CommissionerController::class, 'dashboard'])->name('dashboard');
    Route::get('/map', [MapController::class, 'commissionerMap'])->name('map');
    // Zone routes
    Route::get('zones/by-corporation', [ZoneController::class, 'getZonesByCorporation'])->name('zones.byCorporation');
    Route::get('zones/list', [ZoneController::class, 'list'])->name('zone.list');
    Route::resource('zones', ZoneController::class);
    // Corporation routes
    Route::get('corporations/list', [CorporationController::class, 'list'])->name('corporations.list');
    Route::resource('corporations', CorporationController::class);
    Route::get('corporations/{corporation}', [CorporationController::class, 'show'])->name('corporations.show');
    Route::put('corporations/{corporation}', [CorporationController::class, 'update'])->name('corporations.update');
    // Ward routes
    Route::get('wards/list', [WardController::class, 'list'])->name('ward.list');
    Route::resource('wards', WardController::class);
    Route::get('wards/{ward}', [WardController::class, 'show'])->name('wards.show');
    Route::post('wards', [WardController::class, 'store'])->name('wards.store');
    Route::put('wards/{ward}', [WardController::class, 'update'])->name('wards.update');

    Route::get('map/{id}', [CommissionerController::class, 'showMap'])
        ->name('ward.showmap');
    Route::get('/get-point-details', [CommissionerController::class, 'getPointDetails'])
        ->name('getPointDetails');

    // Add commissioner specific routes here
});

// ─── DC ───────────────────────────────────────────────────
Route::middleware(['auth', 'role:dc'])->prefix('dc')->name('dc.')->group(function () {
    Route::get('/dashboard', [CommissionerController::class, 'dashboard'])->name('dashboard');
});

// ─── AC ───────────────────────────────────────────────────
Route::middleware(['auth', 'role:ac'])->prefix('ac')->name('ac.')->group(function () {
    Route::get('/dashboard', [CommissionerController::class, 'dashboard'])->name('dashboard');
});

// ─── ARO ──────────────────────────────────────────────────
Route::middleware(['auth', 'role:aro'])->prefix('aro')->name('aro.')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    // Add aro specific routes here
});

// ─── BC ───────────────────────────────────────────────────
Route::middleware(['auth', 'role:bc'])->prefix('bc')->name('bc.')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    // Add bc specific routes here
});

// ─── Team Leader ──────────────────────────────────────────
Route::middleware(['auth', 'role:teamleader'])->prefix('teamleader')->name('teamleader.')->group(function () {
    Route::get('/dashboard', [TeamleaderController::class, 'dashboard'])->name('dashboard');
});

// ─── Surveyor ─────────────────────────────────────────────
Route::middleware(['auth', 'role:surveyor'])->prefix('surveyor')->name('surveyor.')->group(function () {
    Route::get('/dashboard', [SurveyorController::class, 'dashboard'])->name('dashboard');
    Route::get('/status', [SurveyorController::class, 'status'])
        ->name('status');
    Route::post('/buildings/save', [PointdataController::class, 'store'])
        ->name('store.pointdata');
    // Add surveyor specific routes here
});
Route::middleware(['auth', 'role:surveyor,teamleader'])->group(function () {


    Route::post('/buildings/save', [PointdataController::class, 'store'])
        ->name('store.buuildingdata ');
    Route::post('/point-data', [PointdataController::class, 'pointDataStore'])
        ->name('store.pointdata');
    Route::post('/line-data', [PointdataController::class, 'lineDataStore'])
        ->name('store.linedata');
    Route::get('/point-data/filter', [PointdataController::class, 'pointDataFilter'])->name('pointdata.filter');
    Route::get('/point-data/{id}', [PointdataController::class, 'editData'])->name('pointdata.edit');
    Route::put('/point-data/{id}', [PointdataController::class, 'pointDataUpdate'])->name('pointdata.update');
});

Route::get('/survey/map', [MapController::class, 'map'])->name('teamleader.map');
