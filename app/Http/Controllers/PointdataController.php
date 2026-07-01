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
        $validator = Validator::make($request->all(), [
            'assessment_type'      => 'required|in:OLD,NEW,VACANT,OTHER_WARD',
            'assessment'           => 'nullable|string|max:100',
            'zone'                 => 'nullable|string|max:50',
            'owner_name'           => 'required|string|max:255',
            'phone_number'         => 'required|digits:10',
            'new_door_no'          => 'required|string|max:100',
            'floor'                => 'required|integer|min:0',
            'bill_usage'           => 'required|in:Residential,Commercial',
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user        = User::find(Auth::id());
        $ward        = Ward::find($user->ward_id);
        $zone        = Zone::find($ward->zone_id);
        $corporation = Corporation::find($zone->corp_id);

        $wardId = $ward->id;
        $corpId = $corporation->id;

        $polygonDataTableName     = "polygon_data_{$wardId}";
        $pointDataTableName       = "point_data_{$wardId}";
        $waterTaxTableName        = "water_tax_{$corpId}";
        $misTableName             = "mis_{$corpId}";
        $ugdTaxTableName          = "ugd_tax_{$corpId}";

        $professionalTaxTableName = "professional_tax_{$corpId}";

        if ($request->assessment_type === 'OLD') {
            $exists = DB::table($misTableName)
                ->where('assessment', $request->assessment)
                ->where('ward_no', $ward->ward_no)
                ->exists();

            if (!$exists) {
                $validator->errors()->add('assessment_type', 'Assessment not found in MIS.');
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors'  => $validator->errors()
                ], 422);
            }
        } elseif ($request->assessment_type === 'NEW') {
            $exists = DB::table($misTableName)
                ->where('assessment', $request->assessment)
                ->exists();

            if ($exists) {
                $validator->errors()->add('assessment_type', 'Assessment already found in MIS.');
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors'  => $validator->errors()
                ], 422);
            }
        } elseif ($request->assessment_type === 'OTHER_WARD') {
            $exists = DB::table($misTableName)
                ->where('assessment', $request->assessment)
                ->whereNotIn('ward_no', [$ward->ward_no])
                ->exists();

            if (!$exists) {
                $validator->errors()->add('assessment_type', 'Assessment not found in MIS for another ward.');
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors'  => $validator->errors()
                ], 422);
            }
        }

        $buildingdata = DB::table($polygonDataTableName)
            ->where('gisid', $request->point_gisid)
            ->first();

        if (!$buildingdata) {
            return response()->json([
                'success' => false,
                'message' => 'Building data not found for the given GIS ID.'
            ], 422);
        }

        if ($request->floor >= $buildingdata->number_floor) {
            $validator->errors()->add('floor', 'Entered floor exceeds the number of floors in the building.');
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        $building_usage = strtoupper(trim($buildingdata->building_usage));
        $bill_usage     = strtoupper(trim($request->bill_usage));

        switch ($building_usage) {
            case 'RESIDENTIAL':
                if ($bill_usage !== 'RESIDENTIAL') {
                    return response()->json(['success' => false, 'message' => 'For Residential buildings, bill usage must be Residential.'], 422);
                }
                break;
            case 'COMMERCIAL':
                if ($bill_usage !== 'COMMERCIAL') {
                    return response()->json(['success' => false, 'message' => 'For Commercial buildings, bill usage must be Commercial.'], 422);
                }
                break;
            case 'MIXED':
                if (!in_array($bill_usage, ['RESIDENTIAL', 'COMMERCIAL'])) {
                    return response()->json(['success' => false, 'message' => 'For Mixed buildings, bill usage must be Residential or Commercial.'], 422);
                }
                break;
            case 'INDUSTRIAL':
                if ($bill_usage !== 'INDUSTRIAL') {
                    return response()->json(['success' => false, 'message' => 'For Industrial buildings, bill usage must be Industrial.'], 422);
                }
                break;
            case 'INSTITUTIONAL':
                if ($bill_usage !== 'INSTITUTIONAL') {
                    return response()->json(['success' => false, 'message' => 'For Institutional buildings, bill usage must be Institutional.'], 422);
                }
                break;
            case 'GOVERNMENT':
                if ($bill_usage !== 'GOVERNMENT') {
                    return response()->json(['success' => false, 'message' => 'For Government buildings, bill usage must be Government.'], 422);
                }
                break;
            case 'VACANT':
                if ($bill_usage !== 'VACANT') {
                    return response()->json(['success' => false, 'message' => 'For Vacant buildings, bill usage must be Vacant.'], 422);
                }
                break;
            default:
                return response()->json(['success' => false, 'message' => 'Invalid building usage.'], 422);
        }

        DB::beginTransaction();

        try {
            if ($request->watertax_no) {
                $watertax = DB::table($waterTaxTableName)
                    ->where('watertax_no', $request->watertax_no)
                    ->first();

                if (!$watertax) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Water tax record not found.'
                    ], 422);
                }

                if ($watertax->gisid && $watertax->gisid !== $request->point_gisid) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Water tax number is already linked to a different GIS ID.'
                    ], 422);
                }

                DB::table($waterTaxTableName)
                    ->where('watertax_no', $request->watertax_no)
                    ->update(['gisid' => $request->point_gisid]);
            }



            if ($request->ugd_no) {
                $ugd = DB::table($ugdTaxTableName)
                    ->where('ugd_no', $request->ugd_no)
                    ->first();

                if (!$ugd) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'UGD record not found.'
                    ], 422);
                }

                if ($ugd->gisid && $ugd->gisid !== $request->point_gisid) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'UGD number is already linked to a different GIS ID.'
                    ], 422);
                }

                DB::table($ugdTaxTableName)
                    ->where('ugd_no', $request->ugd_no)
                    ->update([
                        'gisid'               => $request->point_gisid,
                        'usage'           => $request->ugd_usage,
                        'DBC_type'        => $request->ugd_DBC_type,
                        'slab_description' => $request->ugd_slab_description,
                    ]);
            }


            $professionalTaxRecord = DB::table($professionalTaxTableName)
                ->where('assessment', $request->assessment)
                ->first();

            if ($professionalTaxRecord) {
                if ($professionalTaxRecord->gisid && $professionalTaxRecord->gisid !== $request->point_gisid) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Professional tax record is already linked to a different GIS ID.'
                    ], 422);
                }

                DB::table($professionalTaxTableName)
                    ->where('assessment', $request->assessment)
                    ->update(['gisid' => $request->point_gisid]);
            }

            DB::table($pointDataTableName)->insert([
                'building_id' => $buildingdata->id,
                'assessment_type'      => $request->assessment_type,
                'assessment'           => $request->assessment,
                'owner_name'           => $request->owner_name,
                'phone_number'         => $request->phone_number,
                'new_door_no'          => $request->new_door_no,
                'floor'                => $request->floor,
                'bill_usage'           => $request->bill_usage,
                'old_assessment'       => $request->old_assessment,
                'present_owner_name'   => $request->present_owner_name,
                'old_door_no'          => $request->old_door_no,
                'aadhar_no'            => $request->aadhar_no,
                'ration_no'            => $request->ration_no,
                'number_persons'       => $request->number_persons,
                'eb'                   => $request->eb,
                'worker_name'          => $user->name,
                'remarks'              => $request->remarks,
                'water_tax'          => $request->watertax_no,
                'point_gisid'          => $request->point_gisid,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Point data stored successfully.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.',
                'error'   => $e->getMessage()
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
}
