<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Models\Corporation;
use App\Models\User;
use App\Models\Ward;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
class PointdataController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'building_gisid' => 'required|string|max:50',
            'number_bill' => 'required|integer',
            'number_shop' => 'required|integer|min:0',
            'number_floor' => 'required|integer|min:0',
            'building_name' => 'nullable|string|max:255',
            'new_address' => 'nullable|string|max:500',
            'road_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'building_usage' => 'required|in:RESIDENTIAL,COMMERCIAL,MIXED,INDUSTRIAL,INSTITUTIONAL,GOVERNMENT,VACANT',
            'construction_type' => 'required|in:PERMANENT,SEMI_PERMANENT,VACANT_LAND,SHED,CAR_SHED,TEMPORARY',
            'building_type' => 'required|in:Independent,Flat,Kalyana_Mandapam,Hotel,Cinema_Theatre,Central_Government_Building,State_Government_Building,Municipality_Corporation,Educational_Institution,Hospital,Commercial_Complex,Shop,Office,Temple,Mosque,Church,Amma_Unavagam,Public_Toilet,Vacant Land,Under Construction,Others',
            'ugd' => 'nullable|in:No_Connection,Manhole_Available_but_Connection_Not_Given_to_House,Stage_1_Completed,Stage_1_2_Completed,Stage_1_2_Completed_but_Not_Connected,Stage_1_2_3_Completed,Direct_Connection_Given,1_UGD_Connection_-_3_Stage_Completed,2_UGD_Connection_-_3_Stage_Completed',
            'liftroom' => 'nullable|in:Yes,No',
            'headroom' => 'nullable|in:Yes,No',
            'overhead_tank' => 'nullable|in:Yes,No',
            'rainwater_harvesting' => 'nullable|in:Yes,No',
            'parking' => 'nullable|in:Yes,No',
            'ramp' => 'nullable|in:Yes,No',
            'hoarding' => 'nullable|in:Yes,No',
            'cctv' => 'nullable|in:Yes,No',
            'zone' => 'nullable',
            'cell_tower' => 'nullable|in:Yes,No',
            'solar_panel' => 'nullable|in:Yes,No',
            'basement' => 'required|integer|min:0|max:5',
            'water_connection' => 'nullable',
            'percentage' => 'required|numeric|min:0|max:100',
            'remarks' => 'nullable|string|max:500',
            'corporationremarks' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpg,jpeg,png',
            'image2' => 'nullable|image|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $user = User::find(Auth::id());
            $ward = Ward::find($user->ward_id);
            $wardId = $ward->id;
            $zone = Zone::find($ward->zone_id);
            $corporation = Corporation::find($zone->corp_id);
            $corp = $corporation->id;
            $polygonDataTableName = "polygon_data_{$wardId}";

            $existingRecord = DB::table($polygonDataTableName)
                ->where('gisid', $data['building_gisid'])
                ->first();

            // ========== IMAGE VALIDATION LOGIC ==========
            if (!$existingRecord && !$request->hasFile('image') && !$request->hasFile('image2')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => [
                        'image' => ['At least one image is required for new building records.']
                    ]
                ], 422);
            }

            // Flat validation
            if ($request->building_type == "Flat" && $request->number_floor < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => [
                        'number_floor' => [
                            'Flat building must have at least 3 floors.'
                        ]
                    ]
                ], 422);
            }

            // Relative path under public/ for this corp + table (helper creates the dir if missing)
            $relativeUploadDir = "uploads/{$corp}/{$polygonDataTableName}";

            $imagePath1 = null;
            $imagePath2 = null;

            // Process image1 (overwrite if uploaded)
            if ($request->hasFile('image')) {
                $imagePath1 = CommonHelper::uploadProfileImage(
                    $request->file('image'),
                    $relativeUploadDir
                );

                // Delete old image if exists and we're updating
                if ($existingRecord && !empty($existingRecord->image)) {
                    CommonHelper::deleteProfileImage($existingRecord->image);
                }
            }

            // Process image2 (overwrite if uploaded)
            if ($request->hasFile('image2')) {
                $imagePath2 = CommonHelper::uploadProfileImage(
                    $request->file('image2'),
                    $relativeUploadDir
                );

                // Delete old image if exists and we're updating
                if ($existingRecord && !empty($existingRecord->image2)) {
                    CommonHelper::deleteProfileImage($existingRecord->image2);
                }
            }

            // Prepare insert/update data
            $insertData = [
                'gisid' => $data['building_gisid'],
                'number_bill' => $data['number_bill'] ?? null,
                'number_shop' => $data['number_shop'],
                'number_floor' => $data['number_floor'],
                'building_name' => $data['building_name'] ?? null,

                'road_name' => $data['road_name'],
                'phone' => $data['phone'] ?? null,
                'building_usage' => $data['building_usage'],
                'construction_type' => $data['construction_type'],
                'building_type' => $data['building_type'],
                'ugd' => $data['ugd'] ?? null,
                'liftroom' => $data['liftroom'] ?? 'No',
                'headroom' => $data['headroom'] ?? 'No',
                'overhead_tank' => $data['overhead_tank'] ?? 'No',
                'rainwater_harvesting' => $data['rainwater_harvesting'] ?? 'No',
                'parking' => $data['parking'] ?? 'No',
                'ramp' => $data['ramp'] ?? 'No',
                'hoarding' => $data['hoarding'] ?? 'No',
                'cctv' => $data['cctv'] ?? 'No',
                'zone' => $data['zone'] ?? 'No',
                'cell_tower' => $data['cell_tower'] ?? 'No',
                'solar_panel' => $data['solar_panel'] ?? 'No',
                'basement' => $data['basement'],
                'water_connection' => $data['water_connection'] ?? null,
                'percentage' => $data['percentage'],
                'remarks' => $data['remarks'] ?? null,
                'worker_name' => $user->id . '-' . $user->name,
                'corporationremarks' => $data['corporationremarks'] ?? null,
                'updated_at' => now(),
            ];

            // Handle image paths with proper null checks
            if ($imagePath1) {
                $insertData['image'] = $imagePath1;
            } elseif ($existingRecord && !empty($existingRecord->image)) {
                $insertData['image'] = $existingRecord->image;
            } else {
                $insertData['image'] = null;
            }

            if ($imagePath2) {
                $insertData['image2'] = $imagePath2;
            } elseif ($existingRecord && !empty($existingRecord->image2)) {
                $insertData['image2'] = $existingRecord->image2;
            } else {
                $insertData['image2'] = null;
            }

            // UPDATE or INSERT
            if ($existingRecord) {
                $pointDataTableName = "point_data_{$wardId}";

                if (Schema::hasTable($pointDataTableName)) {
                    $count = DB::table($pointDataTableName)
                        ->where('point_gisid', $existingRecord->gisid)
                        ->count();

                    if ($count > 0) {
                        $currentNumberBill = $data['number_bill'] ?? null;
                        $existingNumberBill = $existingRecord->number_bill ?? null;

                        $numberBillToCheck = $currentNumberBill !== null ? $currentNumberBill : $existingNumberBill;

                        if ($numberBillToCheck !== null && $numberBillToCheck < $count) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Validation errors',
                                'errors' => [
                                    'number_bill' => [
                                        'Number of bills (' . $numberBillToCheck . ') cannot be less than the number of point assessments (' . $count . ')'
                                    ]
                                ]
                            ], 422);
                        }
                    }
                }

                DB::table($polygonDataTableName)
                    ->where('gisid', $data['building_gisid'])
                    ->update($insertData);

                $message = 'Building data updated successfully';
            } else {
                $insertData['created_at'] = now();
                DB::table($polygonDataTableName)->insert($insertData);
                $message = 'Building data saved successfully';
            }

            $updatedRecord = DB::table($polygonDataTableName)
                ->where('gisid', $data['building_gisid'])
                ->first();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $updatedRecord,
                'polygonDatas' => DB::table($polygonDataTableName)->get()
            ]);
        } catch (\Exception $e) {
            Log::error('Polygon data upload error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function pointDataStore(Request $request)
    {
        // Base validation rules
        $validator = Validator::make($request->all(), [
            'assessment_type'      => 'required|in:OLD,NEW,VACANT,OTHER_WARD',
            'assessment'           => 'nullable|string|max:100',
            'zone'                 => 'nullable|string|max:50',
            'owner_name'           => 'required|string|max:255',
            'phone_number'         => 'required|digits:10',
            'new_door_no'          => 'required|string|max:100',
            'floor'                => 'required|integer|min:0',
            'bill_usage'           => 'required|in:RESIDENTIAL,COMMERCIAL',
            'old_assessment'       => 'nullable|string|max:100',
            'present_owner_name'   => 'required|string|max:255',
            'old_door_no'          => 'required|string|max:100',
            'aadhar_no'            => 'nullable|digits:12',
            'ration_no'            => 'nullable|string|max:100',
            'number_persons'       => 'nullable|integer|min:1',
            'eb'                   => 'nullable|string|max:100',
            'worker_name'          => 'nullable|string|max:255',
            'remarks'              => 'nullable|string',
            'watertax_no'          => 'nullable|string|max:100',
            'old_watertax_no'      => 'nullable|string|max:100',
            'water_usage'          => 'nullable|in:Domestic,Commercial,Industrial,Institutional',
            'water_DBC_type'       => 'nullable|string|max:100',
            'water_slab_description' => 'nullable|string',
            'ugd_no'               => 'nullable|string|max:100',
            'old_ugd_no'           => 'nullable|string|max:100',
            'ugd_usage'            => 'nullable|string|max:100',
            'ugd_DBC_type'         => 'nullable|string|max:100',
            'ugd_slab_description' => 'nullable|string',
            'point_gisid'          => 'required|string|max:100',
            'professional'         => 'nullable|array',
            'professional.*.pt_number' => 'nullable|string|max:100',
            'professional.*.old_pt_number' => 'nullable|string|max:100',
            'professional.*.establishment_name' => 'nullable|string|max:255',
            'professional.*.profession_type' => 'nullable|string|max:100',
            'professional.*.employee_count' => 'nullable|integer|min:0',
            'professional.*.half_year_tax' => 'nullable|numeric|min:0',
            'professional.*.pt_remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $user = User::find(Auth::id());

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $ward = Ward::find($user->ward_id);
            if (!$ward) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ward not found for the user'
                ], 404);
            }

            $zone = Zone::find($ward->zone_id);
            if (!$zone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zone not found for the ward'
                ], 404);
            }

            $corporation = Corporation::find($zone->corp_id);
            if (!$corporation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Corporation not found for the zone'
                ], 404);
            }

            $wardId = $ward->id;
            $wardNo = $ward->ward_no;
            $zoneId = $zone->id;
            $corpId = $corporation->id;

            $polygonDataTableName = "polygon_data_{$wardId}";
            $pointDataTableName = "point_data_{$wardId}";
            $waterTaxTableName = "water_tax_{$corpId}";
            $misTableName = "mis_{$corpId}";
            $ugdTaxTableName = "ugd_tax_{$corpId}";
            $professionalTaxTableName = "professional_tax_{$corpId}";

            // Check if tables exist
            if (!Schema::hasTable($misTableName)) {
                return response()->json([
                    'success' => false,
                    'message' => "MIS table ({$misTableName}) not found"
                ], 422);
            }

            // Check if point data already exists
            $exist = DB::table($pointDataTableName)
                ->where('assessment', $request->assessment)
                ->exists();

            if ($exist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => [
                        'assessment_type' => ['Assessment Already entered.']
                    ]
                ], 422);
            }

            // Validate assessment type
            if ($request->assessment_type === 'OLD') {
                $exists = DB::table($misTableName)
                    ->where('assessment', $request->assessment)
                    ->where('ward_no', $wardNo)
                    ->exists();

                if (!$exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation errors',
                        'errors' => [
                            'assessment_type' => ['Assessment not found in MIS for this ward.']
                        ]
                    ], 422);
                }
            } elseif ($request->assessment_type === 'NEW') {
                $exists = DB::table($misTableName)
                    ->where('assessment', $request->assessment)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation errors',
                        'errors' => [
                            'assessment_type' => ['Assessment already exists in MIS.']
                        ]
                    ], 422);
                }
            } elseif ($request->assessment_type === 'OTHER_WARD') {
                $exists = DB::table($misTableName)
                    ->where('assessment', $request->assessment)
                    ->where('ward_no', '!=', $wardNo)
                    ->exists();

                if (!$exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation errors',
                        'errors' => [
                            'assessment_type' => ['Assessment not found in MIS for another ward.']
                        ]
                    ], 422);
                }
            }

            // Get building data
            $buildingdata = DB::table($polygonDataTableName)
                ->where('gisid', $request->point_gisid)
                ->first();

            if (!$buildingdata) {
                return response()->json([
                    'success' => false,
                    'message' => 'Building data not found for the given GIS ID'
                ], 422);
            }

            // Validate floor
            if ($request->floor >= $buildingdata->number_floor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => [
                        'floor' => ['Entered floor exceeds the number of floors in the building.']
                    ]
                ], 422);
            }

            // Validate building usage
            $building_usage = strtoupper(trim($buildingdata->building_usage ?? ''));
            $bill_usage = strtoupper(trim($request->bill_usage ?? ''));

            $validUsageMap = [
                'RESIDENTIAL' => ['RESIDENTIAL'],
                'COMMERCIAL' => ['COMMERCIAL'],
                'MIXED' => ['RESIDENTIAL', 'COMMERCIAL'],
                'INDUSTRIAL' => ['INDUSTRIAL'],
                'INSTITUTIONAL' => ['INSTITUTIONAL'],
                'GOVERNMENT' => ['GOVERNMENT'],
                'VACANT' => ['VACANT'],
            ];

            if (!isset($validUsageMap[$building_usage])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid building usage',
                    'errors' => [
                        'bill_usage' => ["Invalid building usage '{$building_usage}'"]
                    ]
                ], 422);
            }

            if (!in_array($bill_usage, $validUsageMap[$building_usage])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => [
                        'bill_usage' => ["For {$building_usage} buildings, bill usage must be " . implode(' or ', $validUsageMap[$building_usage])]
                    ]
                ], 422);
            }

            // Check if tables exist before using them
            if (!Schema::hasTable($waterTaxTableName)) {
                return response()->json([
                    'success' => false,
                    'message' => "Water tax table ({$waterTaxTableName}) not found"
                ], 422);
            }

            if (!Schema::hasTable($ugdTaxTableName)) {
                return response()->json([
                    'success' => false,
                    'message' => "UGD tax table ({$ugdTaxTableName}) not found"
                ], 422);
            }

            if (!Schema::hasTable($professionalTaxTableName)) {
                return response()->json([
                    'success' => false,
                    'message' => "Professional tax table ({$professionalTaxTableName}) not found"
                ], 422);
            }

            DB::beginTransaction();

            try {
                $validationErrors = [];

                // ============================================================
                // 1. WATER TAX - INSERT IF NOT EXISTS
                // ============================================================
                if ($request->filled('watertax_no')) {
                    $watertax = DB::table($waterTaxTableName)
                        ->where('watertax_no', $request->watertax_no)
                        ->first();

                    if (!$watertax) {
                        // INSERT NEW WATER TAX RECORD
                        DB::table($waterTaxTableName)->insert([
                            'corporation_id' => $corpId,
                            'ward_no' => $wardNo,
                            'gisid' => $request->point_gisid,
                            'assessment' => $request->assessment,
                            'watertax_no' => $request->watertax_no,
                            'old_watertax_no' => $request->old_watertax_no ?? null,
                            'usage' => $request->water_usage ?? null,
                            'DBC_type' => $request->water_DBC_type ?? null,
                            'slab_description' => $request->water_slab_description ?? null,
                            'remarks' => "NEW WATERTAX",
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        // CHECK IF LINKED TO DIFFERENT GIS
                        if (!empty($watertax->gisid) && $watertax->gisid !== $request->point_gisid) {
                            $validationErrors['watertax_no'] = ['Water tax number is already linked to a different GIS ID.'];
                        } else {
                            // UPDATE EXISTING WATER TAX
                            DB::table($waterTaxTableName)
                                ->where('watertax_no', $request->watertax_no)
                                ->update([
                                    'ward_no' => $wardNo,
                                    'gisid' => $request->point_gisid,
                                    'usage' => $request->water_usage ?? $watertax->usage,
                                    'DBC_type' => $request->water_DBC_type ?? $watertax->DBC_type,
                                    'slab_description' => $request->water_slab_description ?? $watertax->slab_description,
                                    'updated_at' => now(),
                                ]);
                        }
                    }
                }

                // ============================================================
                // 2. UGD TAX - INSERT IF NOT EXISTS
                // ============================================================
                if ($request->filled('ugd_no')) {
                    $ugd = DB::table($ugdTaxTableName)
                        ->where('ugd_no', $request->ugd_no)
                        ->first();

                    if (!$ugd) {
                        // INSERT NEW UGD RECORD
                        DB::table($ugdTaxTableName)->insert([
                            'corporation_id' => $corpId,
                            'ward_no' => $wardNo,
                            'gisid' => $request->point_gisid,
                            'assessment' => $request->assessment,
                            'ugd_no' => $request->ugd_no,
                            'old_ugd_no' => $request->old_ugd_no ?? null,
                            'usage' => $request->ugd_usage ?? null,
                            'DBC_type' => $request->ugd_DBC_type ?? null,
                            'slab_description' => $request->ugd_slab_description ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        // CHECK IF LINKED TO DIFFERENT GIS
                        if (!empty($ugd->gisid) && $ugd->gisid !== $request->point_gisid) {
                            $validationErrors['ugd_no'] = ['UGD number is already linked to a different GIS ID.'];
                        } else {
                            // UPDATE EXISTING UGD
                            DB::table($ugdTaxTableName)
                                ->where('ugd_no', $request->ugd_no)
                                ->update([
                                    'ward_no' => $wardNo,
                                    'gisid' => $request->point_gisid,
                                    'usage' => $request->ugd_usage ?? $ugd->usage,
                                    'DBC_type' => $request->ugd_DBC_type ?? $ugd->DBC_type,
                                    'slab_description' => $request->ugd_slab_description ?? $ugd->slab_description,
                                    'updated_at' => now(),
                                ]);
                        }
                    }
                }

                // ============================================================
                // 3. PROFESSIONAL TAX - INSERT IF NOT EXISTS
                // ============================================================
                if ($request->has('professional') && is_array($request->professional)) {
                    foreach ($request->professional as $index => $professional) {
                        if (empty($professional['pt_number'])) {
                            continue;
                        }

                        $existing = DB::table($professionalTaxTableName)
                            ->where('pt_number', $professional['pt_number'])
                            ->first();

                        if ($existing) {
                            // CHECK IF LINKED TO DIFFERENT GIS
                            if (!empty($existing->gisid) && $existing->gisid != $request->point_gisid) {
                                $validationErrors["professional.{$index}.pt_number"] = [
                                    "Professional Tax Number '{$professional['pt_number']}' is already linked with another GIS ID."
                                ];
                            } else {
                                // UPDATE EXISTING PROFESSIONAL TAX
                                DB::table($professionalTaxTableName)
                                    ->where('id', $existing->id)
                                    ->update([
                                        'corporation_id' => $corpId,
                                        'ward_no' => $wardNo,
                                        'gisid' => $request->point_gisid,
                                        'assessment' => $request->assessment,
                                        'old_pt_number' => $professional['old_pt_number'] ?? $existing->old_pt_number,
                                        'establishment_name' => $professional['establishment_name'] ?? $existing->establishment_name,
                                        'profession_type' => $professional['profession_type'] ?? $existing->profession_type,
                                        'employee_count' => $professional['employee_count'] ?? $existing->employee_count,
                                        'half_year_tax' => $professional['half_year_tax'] ?? $existing->half_year_tax,
                                        'remarks' => $professional['pt_remarks'] ?? $existing->remarks,
                                        'updated_at' => now(),
                                    ]);
                            }
                        } else {

                            // INSERT NEW PROFESSIONAL TAX
                            DB::table($professionalTaxTableName)->insert([
                                'corporation_id' => $corpId,
                                'ward_no' => $wardNo,
                                'gisid' => $request->point_gisid,
                                'assessment'         => $request->assessment,
                                'owner_name'         => $request->owner_name,
                                'phone_number'         => $request->phone_number ?? null,
                                'pt_number' => $professional['pt_number'],
                                'old_pt_number' => $professional['old_pt_number'] ?? null,
                                'establishment_name' => $professional['establishment_name'] ?? null,
                                'profession_type' => $professional['profession_type'] ?? null,
                                'employee_count' => $professional['employee_count'] ?? null,
                                'half_year_tax' => $professional['half_year_tax'] ?? null,
                                'remarks' => $professional['pt_remarks'] ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                // ============================================================
                // 4. MIS TABLE - INSERT/UPDATE (REMOVED zone_id)
                // ============================================================
                $misData = [
                    'corporation_id' => $corpId,
                    'gisid' => $request->point_gisid,
                    'ward_no' => $wardNo,
                    'assessment' => $request->assessment,
                    'old_assessment' => $request->old_assessment ?? null,
                    'road_name' => $request->road_name ?? null,
                    'owner_name' => $request->owner_name,
                    'old_door_no' => $request->old_door_no,
                    'new_door_no' => $request->new_door_no,
                    'phone_number' => $request->phone_number,
                    'plot_area' => $request->plot_area ?? null,
                    'half_year_tax' => $request->half_year_tax ?? null,
                    'balance' => $request->balance ?? null,
                    'usage' => $request->bill_usage ?? null,
                    'type' => $request->type ?? null,
                    'zone' => $request->zone ?? null,
                    'updated_at' => now(),
                ];
                // Check if MIS record exists
                $misExists = DB::table($misTableName)
                    ->where('assessment', $request->assessment)
                    ->exists();

                if ($misExists) {
                    // UPDATE MIS
                    DB::table($misTableName)
                        ->where('assessment', $request->assessment)
                        ->update($misData);
                } else {
                    // INSERT MIS
                    $misData['created_at'] = now();
                    DB::table($misTableName)->insert($misData);
                }

                // ============================================================
                // 5. If any validation errors exist, rollback
                // ============================================================
                if (!empty($validationErrors)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation errors',
                        'errors' => $validationErrors
                    ], 422);
                }

                // ============================================================
                // 6. INSERT POINT DATA
                // ============================================================
                DB::table($pointDataTableName)->insert([
                    'building_data_id' => $buildingdata->id,
                    'assessment_type' => $request->assessment_type,
                    'assessment' => $request->assessment,
                    'owner_name' => $request->owner_name,
                    'phone_number' => $request->phone_number,
                    'new_door_no' => $request->new_door_no,
                    'floor' => $request->floor,
                    'bill_usage' => $request->bill_usage,
                    'old_assessment' => $request->old_assessment,
                    'present_owner_name' => $request->present_owner_name,
                    'old_door_no' => $request->old_door_no,
                    'aadhar_no' => $request->aadhar_no,
                    'ration_no' => $request->ration_no,
                    'no_of_persons' => $request->number_persons,
                    'eb' => $request->eb,
                    'worker_name' => $user->id ?? null,
                    'remarks' => $request->remarks,
                    'water_tax' => $request->watertax_no,
                    'point_gisid' => $request->point_gisid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Point data stored successfully.',
                    'data' => [
                        'assessment' => $request->assessment,
                        'gisid' => $request->point_gisid,
                        'ward_no' => $wardNo,
                        'water_tax_processed' => $request->filled('watertax_no') ? true : false,
                        'ugd_processed' => $request->filled('ugd_no') ? true : false,
                        'professional_tax_processed' => $request->has('professional') ? count($request->professional) : 0,
                        'mis_updated' => $misExists ? 'updated' : 'inserted',
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage(),
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function lineDataStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gisid'     => 'required|string|max:50',
            'road_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        if (!$user || !$user->ward_id) {
            return response()->json([
                'success' => false,
                'message' => 'Ward not assigned to user.'
            ], 400);
        }

        $lineTableName = "lines_{$user->ward_id}";

        // Check table exists
        if (!Schema::hasTable($lineTableName)) {
            return response()->json([
                'success' => false,
                'message' => "Table {$lineTableName} not found."
            ], 404);
        }

        // Find line first
        $line = DB::table($lineTableName)
            ->where('gisid', $request->gisid)
            ->first();

        if (!$line) {
            return response()->json([
                'success' => false,
                'message' => 'GIS ID not found.'
            ], 404);
        }

        // Update
        DB::table($lineTableName)
            ->where('gisid', $request->gisid)
            ->update([
                'road_name' => $request->road_name,
                'updated_at' => now()
            ]);
        $lines = DB::table($lineTableName)->get();
        return response()->json([
            'success' => true,
            'message' => 'Road name updated successfully.',
            'lines' => $lines
        ]);
    }




    public function editData(Request $request, $id)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }

            $ward = Ward::find($user->ward_id);
            $zone = Zone::find($ward->zone_id);
            $corporation = Corporation::find($zone->corp_id);

            $wardId = $ward->id;
            $corpId = $corporation->id;

            $pointDataTable        = "point_data_{$wardId}";
            $waterTaxTable         = "water_tax_{$corpId}";
            $ugdTaxTable           = "ugd_tax_{$corpId}";
            $professionalTaxTable  = "professional_tax_{$corpId}";

            $pointData = DB::table($pointDataTable)->where('id', $id)->first();

            if (!$pointData) {
                return response()->json(['success' => false, 'message' => 'Point data not found'], 404);
            }

            $waterTax = DB::table($waterTaxTable)
                ->where('assessment', $pointData->assessment)
                ->first();

            $ugdTax = DB::table($ugdTaxTable)
                ->where('assessment', $pointData->assessment)
                ->first();

            $professionalTax = DB::table($professionalTaxTable)
                ->where('gisid', $pointData->point_gisid)
                ->where('assessment', $pointData->assessment)
                ->get();

            return response()->json([
                'success'      => true,
                'point_data'   => $pointData,
                'water_tax'    => $waterTax,
                'ugd_tax'      => $ugdTax,
                'professional' => $professionalTax,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }



    public function pointDataUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            // Point Data fields
            'point_gisid'          => 'required|string|max:100',
            'assessment_type'      => 'required|in:OLD,NEW,VACANT,OTHER_WARD',
            'assessment'           => 'required|string|max:100',
            'old_assessment'       => 'nullable|string|max:100',
            'zone'                 => 'required|string|max:50',
            'owner_name'           => 'required|string|max:255',
            'present_owner_name'   => 'nullable|string|max:255',
            'phone_number'         => 'required|digits:10',
            'old_door_no'          => 'nullable|string|max:100',
            'new_door_no'          => 'required|string|max:100',
            'aadhar_no'            => 'nullable|digits:12',
            'ration_no'            => 'nullable|string|max:50',
            'floor'                => 'required|integer|min:0',
            'number_persons'       => 'nullable|integer|min:0',
            'bill_usage'           => 'required|in:RESIDENTIAL,COMMERCIAL',
            'eb'                   => 'nullable|string|max:50',
            'worker_name'          => 'nullable|string|max:255',
            'remarks'              => 'nullable|string',

            // Water Tax fields
            'watertax_no'          => 'nullable|string|max:100',
            'old_watertax_no'      => 'nullable|string|max:100',
            'water_usage'          => 'nullable|string|max:100',
            'water_DBC_type'       => 'nullable|string|max:100',
            'water_slab_description' => 'nullable|string',

            // UGD Tax fields
            'ugd_no'               => 'nullable|string|max:100',
            'old_ugd_no'           => 'nullable|string|max:100',
            'ugd_usage'            => 'nullable|string|max:100',
            'ugd_DBC_type'         => 'nullable|string|max:100',
            'ugd_slab_description' => 'nullable|string',

            // Professional Tax fields
            'professional'         => 'nullable|array',
            'professional.*.id'    => 'nullable|integer',
            'professional.*.pt_number' => 'nullable|string|max:100',
            'professional.*.old_pt_number' => 'nullable|string|max:100',
            'professional.*.establishment_name' => 'nullable|string|max:255',
            'professional.*.profession_type' => 'nullable|string|max:100',
            'professional.*.employee_count' => 'nullable|integer|min:0',
            'professional.*.half_year_tax' => 'nullable|numeric|min:0',
            'professional.*.pt_remarks' => 'nullable|string',

            'removed_professional_ids' => 'nullable|array',
            'removed_professional_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $user = User::find(Auth::id());
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }

            $ward = Ward::find($user->ward_id);
            if (!$ward) {
                return response()->json(['success' => false, 'message' => 'Ward not found'], 404);
            }

            $zone = Zone::find($ward->zone_id);
            if (!$zone) {
                return response()->json(['success' => false, 'message' => 'Zone not found'], 404);
            }

            $corporation = Corporation::find($zone->corp_id);
            if (!$corporation) {
                return response()->json(['success' => false, 'message' => 'Corporation not found'], 404);
            }

            $wardId = $ward->id;
            $corpId = $corporation->id;

            $pointDataTable       = "point_data_{$wardId}";
            $waterTaxTable        = "water_tax_{$corpId}";
            $ugdTaxTable          = "ugd_tax_{$corpId}";
            $professionalTaxTable = "professional_tax_{$corpId}";

            // Check if point data exists
            $existing = DB::table($pointDataTable)->where('id', $id)->first();
            if (!$existing) {
                return response()->json(['success' => false, 'message' => 'Point data not found'], 404);
            }

            DB::beginTransaction();
            try {
                // 1. Update point_data - Added missing fields
                DB::table($pointDataTable)->where('id', $id)->update([
                    'point_gisid'          => $request->point_gisid,
                    'assessment_type'      => $request->assessment_type,
                    'assessment'           => $request->assessment,
                    'old_assessment'       => $request->old_assessment,
                    'zone'                 => $request->zone,
                    'owner_name'           => $request->owner_name,
                    'present_owner_name'   => $request->present_owner_name,
                    'phone_number'         => $request->phone_number,
                    'old_door_no'          => $request->old_door_no,
                    'new_door_no'          => $request->new_door_no,
                    'aadhar_no'            => $request->aadhar_no,
                    'ration_no'            => $request->ration_no,
                    'floor'                => $request->floor,
                    'no_of_persons'        => $request->number_persons,
                    'bill_usage'           => $request->bill_usage,
                    'eb'                   => $request->eb,
                    'worker_name'          => $request->worker_name,
                    'remarks'              => $request->remarks,
                    'updated_at'           => now(),
                ]);

                // 2. Water tax - Update or Insert
                if ($request->filled('watertax_no')) {
                    $waterTaxExists = DB::table($waterTaxTable)
                        ->where('watertax_no', $request->watertax_no)
                        ->exists();

                    if ($waterTaxExists) {
                        // Update existing
                        DB::table($waterTaxTable)
                            ->where('watertax_no', $request->watertax_no)
                            ->update([
                                'gisid'              => $request->point_gisid,
                                'old_watertax_no'    => $request->old_watertax_no,
                                'usage'              => $request->water_usage,
                                'DBC_type'           => $request->water_DBC_type,
                                'slab_description'   => $request->water_slab_description,
                                'updated_at'         => now(),
                            ]);
                    } else {
                        // Insert new
                        DB::table($waterTaxTable)->insert([
                            'corporation_id'     => $corpId,
                            'gisid'              => $request->point_gisid,
                            'watertax_no'        => $request->watertax_no,
                            'old_watertax_no'    => $request->old_watertax_no,
                            'usage'              => $request->water_usage,
                            'DBC_type'           => $request->water_DBC_type,
                            'slab_description'   => $request->water_slab_description,
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ]);
                    }
                }

                // 3. UGD tax - Update or Insert
                if ($request->filled('ugd_no')) {
                    $ugdExists = DB::table($ugdTaxTable)
                        ->where('ugd_no', $request->ugd_no)
                        ->exists();

                    if ($ugdExists) {
                        // Update existing
                        DB::table($ugdTaxTable)
                            ->where('ugd_no', $request->ugd_no)
                            ->update([
                                'gisid'              => $request->point_gisid,
                                'old_ugd_no'         => $request->old_ugd_no,
                                'usage'              => $request->ugd_usage,
                                'DBC_type'           => $request->ugd_DBC_type,
                                'slab_description'   => $request->ugd_slab_description,
                                'updated_at'         => now(),
                            ]);
                    } else {
                        // Insert new
                        DB::table($ugdTaxTable)->insert([
                            'corporation_id'     => $corpId,
                            'gisid'              => $request->point_gisid,
                            'ugd_no'             => $request->ugd_no,
                            'old_ugd_no'         => $request->old_ugd_no,
                            'usage'              => $request->ugd_usage,
                            'DBC_type'           => $request->ugd_DBC_type,
                            'slab_description'   => $request->ugd_slab_description,
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ]);
                    }
                }

                // 4. Professional tax: update existing / insert new
                if ($request->has('professional') && is_array($request->professional)) {
                    foreach ($request->professional as $prof) {
                        // Skip if pt_number is empty
                        if (empty($prof['pt_number'])) {
                            continue;
                        }

                        if (!empty($prof['id'])) {
                            // Update existing
                            DB::table($professionalTaxTable)
                                ->where('id', $prof['id'])
                                ->where('corporation_id', $corpId) // Security: ensure belongs to this corp
                                ->update([
                                    'pt_number'          => $prof['pt_number'],
                                    'old_pt_number'      => $prof['old_pt_number'] ?? null,
                                    'establishment_name' => $prof['establishment_name'] ?? null,
                                    'profession_type'    => $prof['profession_type'] ?? null,
                                    'employee_count'     => $prof['employee_count'] ?? null,
                                    'half_year_tax'      => $prof['half_year_tax'] ?? null,
                                    'remarks'            => $prof['pt_remarks'] ?? null,
                                    'updated_at'         => now(),
                                ]);
                        } else {
                            // Insert new
                            DB::table($professionalTaxTable)->insert([
                                'corporation_id'     => $corpId,
                                'gisid'              => $request->point_gisid,
                                'ward_no'              => $ward->ward_no,
                                'assessment'         => $request->assessment,
                                'owner_name'         => $request->owner_name,
                                'phone_number'         => $request->phone_number ?? null,
                                'pt_number'          => $prof['pt_number'],
                                'old_pt_number'      => $prof['old_pt_number'] ?? null,
                                'establishment_name' => $prof['establishment_name'] ?? null,
                                'profession_type'    => $prof['profession_type'] ?? null,
                                'employee_count'     => $prof['employee_count'] ?? null,
                                'half_year_tax'      => $prof['half_year_tax'] ?? null,
                                'remarks'            => $prof['pt_remarks'] ?? null,
                                'created_at'         => now(),
                                'updated_at'         => now(),
                            ]);
                        }
                    }
                }

                // 5. Delete professional tax rows the user removed
                if ($request->filled('removed_professional_ids')) {
                    DB::table($professionalTaxTable)
                        ->whereIn('id', $request->removed_professional_ids)
                        ->where('corporation_id', $corpId) // Security: ensure belongs to this corp
                        ->delete();
                }

                DB::commit();

                // Return updated data for frontend
                $updatedData = DB::table($pointDataTable)->where('id', $id)->first();

                return response()->json([
                    'success' => true,
                    'message' => 'Point data updated successfully.',
                    'data' => $updatedData
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Point data update error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Point data update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function pointDataFilter(Request $request)
    {
        $user = User::find(Auth::id());
        $ward = Ward::find($user->ward_id);
        $pointDataTable = "point_data_{$ward->id}";

        $query = DB::table($pointDataTable);

        if ($request->filled('assessment')) {
            $query->where('assessment', 'like', $request->assessment . '%');
        }
        if ($request->filled('old_assessment')) {
            $query->where('old_assessment', 'like', $request->old_assessment . '%');
        }
        if ($request->filled('owner_name')) {
            $query->where('owner_name', 'like', '%' . $request->owner_name . '%');
        }

        $results = $query->orderByDesc('id')->limit(50)->get();

        return response()->json(['success' => true, 'data' => $results]);
    }
public function qrCodeAssessment(Request $request)
{
    $request->validate([
        'point_id' => 'required|integer',
    ]);

    $user = User::find(Auth::id());
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'User not found'], 404);
    }

    $ward = Ward::find($user->ward_id);
    if (!$ward) {
        return response()->json(['success' => false, 'message' => 'Ward not found'], 404);
    }

    $zone = Zone::find($ward->zone_id);
    if (!$zone) {
        return response()->json(['success' => false, 'message' => 'Zone not found'], 404);
    }

    $corporation = Corporation::find($zone->corp_id);
    if (!$corporation) {
        return response()->json(['success' => false, 'message' => 'Corporation not found'], 404);
    }

    $wardId = $ward->id;
    $pointDataTable = "point_data_{$wardId}";
    $pointId = $request->point_id;

    // Check if table exists
    if (!Schema::hasTable($pointDataTable)) {
        return response()->json([
            'success' => false,
            'message' => "Table {$pointDataTable} not found"
        ], 404);
    }

    // Get point data
    $pointData = DB::table($pointDataTable)->where('id', $pointId)->first();

    // FIXED: Proper error message
    if (!$pointData) {
        return response()->json([
            'success' => false,
            'message' => "Point data not found for ID: {$wardId} in table: {$pointDataTable}"
        ], 404);
    }

    // Get assessment number (try different column names)
    $assessmentNumber = $pointData->assessment
        ?? $pointData->assessment_number
        ?? $pointData->Assessment
        ?? 'N/A';

    $wardNumber = $ward->ward_no ?? $ward->ward_number ?? $ward->id;

    // QR data
    $qrData = [
        'ward_no' => $wardNumber,
        'assessment_id' => $pointData->id,
        'assessment_number' => $assessmentNumber,
        'gisid' => $pointData->point_gisid ?? $pointData->gisid ?? null,
    ];

    $qrContent = json_encode($qrData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Generate QR code
    $qrCode = QrCode::format('png')
        ->size(500)
        ->margin(2)
        ->errorCorrection('H')
        ->generate($qrContent);

    $fileName = 'QR_Ward_' . $wardNumber . '_Assessment_' . $assessmentNumber . '.png';

    return response($qrCode)
        ->header('Content-Type', 'image/png')
        ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
}
}
