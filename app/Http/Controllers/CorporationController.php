<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Imports\MisImport;
use App\Imports\ProfessionalTaxImport;
use App\Imports\UgdTaxImport;
use App\Imports\WaterTaxImport;
use App\Models\Corporation;
use App\Services\CorporationService;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Pagination\LengthAwarePaginator;

class CorporationController extends Controller
{
    protected CorporationService $corporationService;
    protected ImportService $importService;

    public function __construct(
        CorporationService $corporationService,
        ImportService $importService
    ) {
        $this->corporationService = $corporationService;
        $this->importService = $importService;
    }

    public function index()
    {
        $user = Auth::user();

        // If commissioner, check if they have a corporation
        if ($user->role == 'commissioner') {
            // Commissioner can only view their own corporation
            return view('main.admin.corporation', ['isCommissioner' => true]);
        }

        return view('main.admin.corporation', ['isCommissioner' => false]);
    }

    public function list(Request $request)
    {
        $user = Auth::user();
        $query = Corporation::query();

        // Commissioner can only view their corporation
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
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Commissioner cannot create new corporations
        if ($user->role == 'commissioner') {
            return response()->json([
                'status' => false,
                'message' => 'Commissioners cannot create new corporations'
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
            'image'                 => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'boundary_file'         => 'required|file',
            'mis_file'              => 'nullable|file|mimes:xlsx,xls,csv|max:10240',
            'water_tax_file'        => 'nullable|file|mimes:xlsx,xls,csv|max:10240',
            'ugd_tax_file'          => 'nullable|file|mimes:xlsx,xls,csv|max:10240',
            'professional_tax_file' => 'nullable|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Upload files
            $profileImagePath = $request->hasFile('image')
                ? CommonHelper::uploadProfileImage($request->file('image'), 'corporation/profile')
                : 'https://ui-avatars.com/api/?name=' . urlencode($request->name) . '&background=1679AB&color=fff';

            $boundary = null;

            if ($request->hasFile('boundary_file')) {
                $geojsonData = json_decode(
                    file_get_contents($request->file('boundary_file')->getRealPath()),
                    true
                );

                if (isset($geojsonData['features'][0]['geometry']['coordinates'])) {
                    $boundary = json_encode(
                        $geojsonData['features'][0]['geometry']['coordinates']
                    );
                } else {
                    throw new \Exception('Invalid GeoJSON format.');
                }
            }

            // Create corporation
            $corporation = Corporation::create([
                'name'          => $request->name,
                'code'          => $request->code,
                'state'         => $request->state,
                'district'      => $request->district,
                'pincode'       => $request->pincode,
                'status'        => $request->status,
                'description'   => $request->description,
                'image'         => $profileImagePath,
                'boundary_file' => $boundary,
            ]);

            // Create corporation tables
            $createTable = $this->corporationService->createCorporationTables($corporation->id);

            if (!$createTable) {
                throw new \Exception('Corporation tables could not be created.');
            }

            $importStats = [];

            // Import MIS file
            if ($request->hasFile('mis_file')) {
                $misImport = new MisImport($corporation->id);
                Excel::import($misImport, $request->file('mis_file'));
                $importStats['mis'] = $misImport->getStats();
            }

            // Import Water Tax file
            if ($request->hasFile('water_tax_file')) {
                $waterTaxImport = new WaterTaxImport($corporation->id);
                Excel::import($waterTaxImport, $request->file('water_tax_file'));
                $importStats['water_tax'] = method_exists($waterTaxImport, 'getStats')
                    ? $waterTaxImport->getStats()
                    : ['message' => 'Imported successfully'];
            }

            // Import UGD Tax file
            if ($request->hasFile('ugd_tax_file')) {
                $ugdTaxImport = new UgdTaxImport($corporation->id);
                Excel::import($ugdTaxImport, $request->file('ugd_tax_file'));
                $importStats['ugd_tax'] = method_exists($ugdTaxImport, 'getStats')
                    ? $ugdTaxImport->getStats()
                    : ['message' => 'Imported successfully'];
            }

            // Import Professional Tax file
            if ($request->hasFile('professional_tax_file')) {
                $professionalTaxImport = new ProfessionalTaxImport($corporation->id);
                Excel::import($professionalTaxImport, $request->file('professional_tax_file'));
                $importStats['professional_tax'] = method_exists($professionalTaxImport, 'getStats')
                    ? $professionalTaxImport->getStats()
                    : ['message' => 'Imported successfully'];
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Corporation created successfully with data imports.',
                'data'    => $corporation,
                'import_stats' => $importStats
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Corporation $corporation)
    {
        $user = Auth::user();

        // Check if commissioner has access to this corporation
        if ($user->role == 'commissioner' && $corporation->id != $user->corporation_id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized to view this corporation'
            ], 403);
        }

        return response()->json(['status' => true, 'data' => $corporation]);
    }

    public function update(Request $request, Corporation $corporation)
    {
        $user = Auth::user();

        // Check if commissioner has access to update this corporation
        if ($user->role == 'commissioner' && $corporation->id != $user->corporation_id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized to update this corporation'
            ], 403);
        }

        // Commissioner can only update limited fields
        $rules = [
            'name'                  => 'required|string|max:255',
            'state'                 => 'required|string|max:255',
            'district'              => 'required|string|max:255',
            'pincode'               => 'required|string|max:20',
            'status'                => 'required|string|max:50',
            'description'           => 'required|string',
            'image'                 => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        // Only admin can change code
        if ($user->role == 'admin') {
            $rules['code'] = 'required|string|max:100|unique:corporations,code,' . $corporation->id;
        }

        // Only admin can upload boundary and tax files
        if ($user->role == 'admin') {
            $rules['boundary_file'] = 'nullable|file';
            $rules['mis_file'] = 'nullable|file|mimes:xlsx,xls,csv|max:10240';
            $rules['water_tax_file'] = 'nullable|file|mimes:xlsx,xls,csv|max:10240';
            $rules['ugd_tax_file'] = 'nullable|file|mimes:xlsx,xls,csv|max:10240';
            $rules['professional_tax_file'] = 'nullable|file|mimes:xlsx,xls,csv|max:10240';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Update image if provided
            if ($request->hasFile('image')) {
                if ($corporation->image && !str_starts_with($corporation->image, 'http')) {
                    Storage::disk('public')->delete($corporation->image);
                }

                $corporation->image = CommonHelper::uploadProfileImage(
                    $request->file('image'),
                    'corporation/profile'
                );
            }

            // Only admin can update boundary
            if ($user->role == 'admin' && $request->hasFile('boundary_file')) {
                $geojsonData = json_decode(
                    file_get_contents($request->file('boundary_file')->getRealPath()),
                    true
                );

                if (isset($geojsonData['features'][0]['geometry']['coordinates'])) {
                    $corporation->boundary = json_encode([
                        'coordinates' => $geojsonData['features'][0]['geometry']['coordinates']
                    ]);
                } else {
                    throw new \Exception('Invalid GeoJSON format.');
                }
            }

            // Update fields
            $corporation->name = $request->name;
            $corporation->state = $request->state;
            $corporation->district = $request->district;
            $corporation->pincode = $request->pincode;
            $corporation->status = $request->status;
            $corporation->description = $request->description;

            // Only admin can update code
            if ($user->role == 'admin' && $request->has('code')) {
                $corporation->code = $request->code;
            }

            $corporation->save();

            $importStats = [];

            // Only admin can import files
            if ($user->role == 'admin') {
                if ($request->hasFile('mis_file')) {
                    $misImport = new MisImport($corporation->id);
                    Excel::import($misImport, $request->file('mis_file'));
                    $importStats['mis'] = $misImport->getStats();
                }

                if ($request->hasFile('water_tax_file')) {
                    $waterTaxImport = new WaterTaxImport($corporation->id);
                    Excel::import($waterTaxImport, $request->file('water_tax_file'));
                    $importStats['water_tax'] = method_exists($waterTaxImport, 'getStats')
                        ? $waterTaxImport->getStats()
                        : ['message' => 'Imported successfully'];
                }

                if ($request->hasFile('ugd_tax_file')) {
                    $ugdTaxImport = new UgdTaxImport($corporation->id);
                    Excel::import($ugdTaxImport, $request->file('ugd_tax_file'));
                    $importStats['ugd_tax'] = method_exists($ugdTaxImport, 'getStats')
                        ? $ugdTaxImport->getStats()
                        : ['message' => 'Imported successfully'];
                }

                if ($request->hasFile('professional_tax_file')) {
                    $professionalTaxImport = new ProfessionalTaxImport($corporation->id);
                    Excel::import($professionalTaxImport, $request->file('professional_tax_file'));
                    $importStats['professional_tax'] = method_exists($professionalTaxImport, 'getStats')
                        ? $professionalTaxImport->getStats()
                        : ['message' => 'Imported successfully'];
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Corporation updated successfully.',
                'data' => $corporation,
                'import_stats' => $importStats,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Corporation $corporation)
    {
        $user = Auth::user();

        // Commissioner cannot delete corporations
        if ($user->role == 'commissioner') {
            return response()->json([
                'status' => false,
                'message' => 'Commissioners cannot delete corporations'
            ], 403);
        }

        DB::beginTransaction();

        try {
            foreach (['image', 'boundary_file'] as $field) {
                if ($corporation->$field && !str_starts_with($corporation->$field, 'http')) {
                    Storage::disk('public')->delete($corporation->$field);
                }
            }

            $this->corporationService->dropCorporationTables($corporation->id);
            $corporation->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Corporation deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
