<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Imports\MisImport;
use App\Imports\ProfessionalTaxImport;
use App\Imports\UgdTaxImport;
use App\Imports\WaterTaxImport;
use App\Models\Corporation;
use App\Services\CorporationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class CorporationController extends Controller
{
    protected CorporationService $corporationService;

    public function __construct(CorporationService $corporationService)
    {
        $this->corporationService = $corporationService;
    }

    public function index()
    {
        $user = Auth::user();

        if ($user->role == 'commissioner') {
            return view('main.admin.corporation', ['isCommissioner' => true]);
        }

        return view('main.admin.corporation', ['isCommissioner' => false]);
    }

    public function list(Request $request)
    {
        try {
            $user = Auth::user();
            $query = Corporation::query();

            if ($user->role == 'commissioner') {
                $query->where('id', $user->corporation_id);
            }

            if ($request->filled('corp_name')) {
                $query->where('name', 'like', '%' . $request->corp_name . '%');
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $corporations = $query->latest()->paginate(12);

            return response()->json([
                'status' => true,
                'data'   => $corporations,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Failed to load corporations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract a clean GeoJSON geometry object from an uploaded boundary file.
     * Returns the full { "type": ..., "coordinates": ... } structure so it
     * round-trips correctly and (if you ever move to a real spatial column)
     * is valid input for ST_GeomFromGeoJSON().
     */
    private function extractBoundaryGeometry($file): array
    {
        $geojsonData = json_decode(file_get_contents($file->getRealPath()), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Boundary file is not valid JSON.');
        }

        $geometry = $geojsonData['features'][0]['geometry'] ?? null;

        if (!$geometry || !isset($geometry['type'], $geometry['coordinates'])) {
            throw new \RuntimeException('Invalid GeoJSON format.');
        }

        return $geometry;
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role == 'commissioner') {
            return response()->json([
                'status'  => false,
                'message' => 'Commissioners cannot create new corporations',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:255',
            'code'                  => 'required|string|max:100|unique:corporations,code',
            'state'                 => 'required|string|max:255',
            'district'              => 'required|string|max:255',
            'pincode'               => 'required|string|max:20',
            'status'                => 'required|string|max:50',
            'description'           => 'required|string',
            'image'                 => 'required|image|mimes:jpg,jpeg,png',
            'boundary_file'         => 'required|file',
            'mis_file'              => 'nullable|file|mimes:xlsx,xls,csv',
            'water_tax_file'        => 'nullable|file|mimes:xlsx,xls,csv',
            'ugd_tax_file'          => 'nullable|file|mimes:xlsx,xls,csv',
            'professional_tax_file' => 'nullable|file|mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Do the lightweight, transaction-safe work (row + tables) inside a transaction.
            $corporation = DB::transaction(function () use ($request) {
                $profileImagePath = $request->hasFile('image')
                    ? CommonHelper::uploadProfileImage($request->file('image'), 'corporation/profile')
                    : 'https://ui-avatars.com/api/?name=' . urlencode($request->name) . '&background=1679AB&color=fff';

                $boundary = null;

                if ($request->hasFile('boundary_file')) {
                    $boundary = json_encode($this->extractBoundaryGeometry($request->file('boundary_file')));
                }

                $corporation = Corporation::create([
                    'name'        => $request->name,
                    'code'        => $request->code,
                    'state'       => $request->state,
                    'district'    => $request->district,
                    'pincode'     => $request->pincode,
                    'status'      => $request->status,
                    'description' => $request->description,
                    'image'       => $profileImagePath,
                    'boundary'    => $boundary,
                ]);

                if (!$this->corporationService->createCorporationTables($corporation->id)) {
                    throw new \RuntimeException('Corporation tables could not be created.');
                }

                return $corporation;
            });

            // Run heavy imports outside the transaction so long-running Excel
            // processing doesn't hold DB locks / risk a dropped connection.
            $importStats = $this->runImports($request, $corporation->id);

            return response()->json([
                'status'       => true,
                'message'      => 'Corporation created successfully with data imports.',
                'data'         => $corporation,
                'import_stats' => $importStats,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Corporation $corporation)
    {
        try {
            $user = Auth::user();

            if ($user->role == 'commissioner' && $corporation->id != $user->corporation_id) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized to view this corporation',
                ], 403);
            }

            return response()->json(['status' => true, 'data' => $corporation]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Failed to load corporation: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Corporation $corporation)
    {
        $user = Auth::user();

        if ($user->role == 'commissioner' && $corporation->id != $user->corporation_id) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized to update this corporation',
            ], 403);
        }

        $rules = [
            'name'        => 'required|string|max:255',
            'state'       => 'required|string|max:255',
            'district'    => 'required|string|max:255',
            'pincode'     => 'required|string|max:20',
            'status'      => 'required|string|max:50',
            'description' => 'required|string',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        if ($user->role == 'admin') {
            $rules['code']                  = 'required|string|max:100|unique:corporations,code,' . $corporation->id;
            $rules['boundary_file']         = 'nullable|file';
            $rules['mis_file']              = 'nullable|file|mimes:xlsx,xls,csv';
            $rules['water_tax_file']        = 'nullable|file|mimes:xlsx,xls,csv';
            $rules['ugd_tax_file']          = 'nullable|file|mimes:xlsx,xls,csv';
            $rules['professional_tax_file'] = 'nullable|file|mimes:xlsx,xls,csv';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $corporation, $user) {
                if ($request->hasFile('image')) {
                    if ($corporation->image && !str_starts_with($corporation->image, 'http')) {
                        Storage::disk('public')->delete($corporation->image);
                    }

                    $corporation->image = CommonHelper::uploadProfileImage(
                        $request->file('image'),
                        'corporation/profile'
                    );
                }

                if ($user->role == 'admin' && $request->hasFile('boundary_file')) {
                    $corporation->boundary = json_encode(
                        $this->extractBoundaryGeometry($request->file('boundary_file'))
                    );
                }

                $corporation->name        = $request->name;
                $corporation->state       = $request->state;
                $corporation->district    = $request->district;
                $corporation->pincode     = $request->pincode;
                $corporation->status      = $request->status;
                $corporation->description = $request->description;

                if ($user->role == 'admin' && $request->has('code')) {
                    $corporation->code = $request->code;
                }

                $corporation->save();
            });

            $importStats = [];

            if ($user->role == 'admin') {
                $importStats = $this->runImports($request, $corporation->id);
            }

            return response()->json([
                'status'       => true,
                'message'      => 'Corporation updated successfully.',
                'data'         => $corporation->fresh(),
                'import_stats' => $importStats,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Corporation $corporation)
    {
        $user = Auth::user();

        if ($user->role == 'commissioner') {
            return response()->json([
                'status'  => false,
                'message' => 'Commissioners cannot delete corporations',
            ], 403);
        }

        try {
            DB::transaction(function () use ($corporation) {
                foreach (['image'] as $field) {
                    if ($corporation->$field && !str_starts_with($corporation->$field, 'http')) {
                        Storage::disk('public')->delete($corporation->$field);
                    }
                }

                $this->corporationService->dropCorporationTables($corporation->id);
                $corporation->delete();
            });

            return response()->json([
                'status'  => true,
                'message' => 'Corporation deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run all optional data imports for a corporation and collect stats.
     * Kept outside DB transactions since Excel imports can be large/slow
     * and shouldn't hold a long-lived transaction open.
     */
    private function runImports(Request $request, int $corporationId): array
    {
        $importStats = [];

        if ($request->hasFile('mis_file')) {
            $misImport = new MisImport($corporationId);
            Excel::import($misImport, $request->file('mis_file'));
            $importStats['mis'] = method_exists($misImport, 'getStats')
                ? $misImport->getStats()
                : ['message' => 'Imported successfully'];
        }

        if ($request->hasFile('water_tax_file')) {
            $waterTaxImport = new WaterTaxImport($corporationId);
            Excel::import($waterTaxImport, $request->file('water_tax_file'));
            $importStats['water_tax'] = method_exists($waterTaxImport, 'getStats')
                ? $waterTaxImport->getStats()
                : ['message' => 'Imported successfully'];
        }

        if ($request->hasFile('ugd_tax_file')) {
            $ugdTaxImport = new UgdTaxImport($corporationId);
            Excel::import($ugdTaxImport, $request->file('ugd_tax_file'));
            $importStats['ugd_tax'] = method_exists($ugdTaxImport, 'getStats')
                ? $ugdTaxImport->getStats()
                : ['message' => 'Imported successfully'];
        }

        if ($request->hasFile('professional_tax_file')) {
            $professionalTaxImport = new ProfessionalTaxImport($corporationId);
            Excel::import($professionalTaxImport, $request->file('professional_tax_file'));
            $importStats['professional_tax'] = method_exists($professionalTaxImport, 'getStats')
                ? $professionalTaxImport->getStats()
                : ['message' => 'Imported successfully'];
        }

        return $importStats;
    }
}
