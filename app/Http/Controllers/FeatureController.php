<?php

namespace App\Http\Controllers;

use App\Services\WardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use geoPHP;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PolygonSplitter;

class FeatureController extends Controller
{
    protected $wardService;

    public function __construct(WardService $wardService)
    {
        $this->wardService = $wardService;
    }
    public function addFeature(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'layer_type' => 'required|string',
            'feature' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $wardId = $user->ward_id;

        if (!$wardId) {
            return response()->json([
                'success' => false,
                'message' => 'User has no ward assigned'
            ], 400);
        }

        try {

            $layerType = trim($request->layer_type);

            switch ($layerType) {
                case 'Polygon':
                    $result = $this->wardService->storeSinglePolygon([
                        'ward_id'   => $wardId,
                        'layer_type' => $layerType,
                        'feature'   => $request->feature
                    ]);
                    break;

                case 'LineString':
                    $result = $this->wardService->createSingleLine([
                        'ward_id'   => $wardId,
                        'layer_type' => $layerType,
                        'feature'   => $request->feature,
                    ]);
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Unsupported layer type: ' . $layerType
                    ], 400);
            }

            return response()->json([
                'success' => $result['status'] ?? true,
                'message' => $result['message'] ?? 'Feature stored successfully',
                'data'    => $result
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile()
            ], 500);
        }
    }



    public function polygonSplit(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $wardId = $user->ward_id;

        if (!$wardId) {
            return response()->json([
                'success' => false,
                'message' => 'User has no ward assigned'
            ], 400);
        }

        $gisid = $request->input('gisid');

        if (!$gisid) {
            return response()->json([
                'success' => false,
                'message' => 'GIS ID is required'
            ], 400);
        }

        $polygon = json_decode($request->polygon, true);
        $line    = json_decode($request->splitLine, true);

        if (!$polygon || !$line) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid polygon or split line data'
            ], 400);
        }

        // Unwrap polygon to a flat ring for Python
        // [[[x,y],...]] → [[x,y],...]
        if (isset($polygon[0][0]) && is_array($polygon[0][0])) {
            $polygonRing = $polygon[0];
        } elseif (isset($polygon[0]) && is_array($polygon[0]) && is_numeric($polygon[0][0])) {
            $polygonRing = $polygon;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Could not extract polygon ring. Expected [[[x,y],...]] or [[x,y],...]'
            ], 400);
        }

        if (count($polygonRing) < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Polygon ring must have at least 3 coordinate pairs'
            ], 400);
        }

        $python = public_path('polygon_split.py');


        if (!file_exists($python)) {
            return response()->json([
                'success' => false,
                'message' => 'Python split script not found at: ' . $python
            ], 500);
        }

        $command = sprintf(
            'python "%s" %s %s 2>&1',
            $python,
            escapeshellarg(json_encode($polygonRing)),
            escapeshellarg(json_encode($line))
        );

        $output = shell_exec($command);

        if (!$output) {
            return response()->json([
                'success'    => false,
                'message'    => 'Python script produced no output',
                'raw_output' => $output
            ], 500);
        }

        $result = json_decode($output, true);

        if (!$result || isset($result['error'])) {
            return response()->json([
                'success'    => false,
                'message'    => $result['error'] ?? 'Failed to split polygon',
                'raw_output' => $output
            ], 500);
        }

        if (count($result) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Split did not produce at least 2 polygons. Ensure the line crosses the polygon fully.'
            ], 422);
        }

        $storeResult = $this->wardService->storeSplitPolygon([
            'ward_id' => $wardId,
            'feature' => $result,
            'gisid'   => $gisid,
        ]);

        return response()->json($storeResult);
    }

    public function polygonUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gisid' => 'required|string',
            'coordinates' => 'required',
            'sqfeet' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $wardId = $user->ward_id;

        if (!$wardId) {
            return response()->json([
                'success' => false,
                'message' => 'User has no ward assigned'
            ], 400);
        }

        $result = $this->wardService->storeUpdatePolygon([
            'ward_id'   => $wardId,
            'gisid'     => $request->gisid,
            'layer_type' => 'Polygon', // Add the layer type
            'feature'   => $request->coordinates, // Map coordinates to feature
            'sqfeet'    => $request->sqfeet ?? '0'
        ]);

        return response()->json([
            'success' => $result['status'] ?? true,
            'message' => $result['message'] ?? 'Feature updated successfully',
            'data'    => $result
        ]);
    }
    public function polygonDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gisid' => 'required|string',
            'type'  => 'required|in:polygon,line',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $wardId = $user->ward_id;

        if (!$wardId) {
            return response()->json([
                'success' => false,
                'message' => 'User has no ward assigned'
            ], 400);
        }

        $data = [
            'ward_id' => $wardId,
            'gisid'   => $request->gisid,
        ];

        // Delete based on type
        if ($request->type === 'polygon') {

            $result = $this->wardService->deletePolygon($data);
        } elseif ($request->type === 'line') {

            $result = $this->wardService->deleteLine($data);
        }

        return response()->json($result);
    }
    public function merge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'primary_gisid'   => 'required|string',
            'secondary_gisid' => 'required|string|different:primary_gisid',
            'sqfeet'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $wardId = $user->ward_id;

        if (!$wardId) {
            return response()->json([
                'success' => false,
                'message' => 'User has no ward assigned',
            ], 400);
        }

        try {

            // ---------------------------------------------------------
            // TABLES
            // ---------------------------------------------------------

            $polygonTable   = 'polygons_' . $wardId;
            $pointTable     = 'points_'   . $wardId;
            $pointDataTable = 'point_data_' . $wardId;

            if (!Schema::hasTable($polygonTable)) {
                return response()->json([
                    'success' => false,
                    'message' => "Polygon table not found: {$polygonTable}",
                ], 404);
            }

            if (!Schema::hasTable($pointTable)) {
                return response()->json([
                    'success' => false,
                    'message' => "Point table not found: {$pointTable}",
                ], 404);
            }

            // ---------------------------------------------------------
            // FETCH POLYGONS
            // ---------------------------------------------------------

            $primaryPolygon = DB::table($polygonTable)
                ->where('gisid', $request->primary_gisid)
                ->first();

            if (!$primaryPolygon) {
                return response()->json([
                    'success' => false,
                    'message' => "Primary polygon not found: {$request->primary_gisid}",
                ], 404);
            }

            $secondaryPolygon = DB::table($polygonTable)
                ->where('gisid', $request->secondary_gisid)
                ->first();

            if (!$secondaryPolygon) {
                return response()->json([
                    'success' => false,
                    'message' => "Secondary polygon not found: {$request->secondary_gisid}",
                ], 404);
            }

            // ---------------------------------------------------------
            // GUARD: BLOCK MERGE IF SECONDARY HAS POINT DATA
            // ---------------------------------------------------------

            if (Schema::hasTable($pointDataTable)) {

                $hasPointData = DB::table($pointDataTable)
                    ->where('point_gisid', $request->secondary_gisid)
                    ->exists();

                if ($hasPointData) {
                    return response()->json([
                        'success'         => false,
                        'merge_allowed'   => false,
                        'message'         => "GISID {$request->secondary_gisid} contains point data. Merge cancelled.",
                        'primary_gisid'   => $request->primary_gisid,
                        'secondary_gisid' => $request->secondary_gisid,
                    ], 409);
                }
            }

            // ---------------------------------------------------------
            // DECODE COORDINATES
            // ---------------------------------------------------------

            $primaryCoordinates   = json_decode($primaryPolygon->coordinates, true);
            $secondaryCoordinates = json_decode($secondaryPolygon->coordinates, true);

            if (!is_array($primaryCoordinates) || empty($primaryCoordinates)) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid coordinates for primary GISID: {$request->primary_gisid}",
                ], 422);
            }

            if (!is_array($secondaryCoordinates) || empty($secondaryCoordinates)) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid coordinates for secondary GISID: {$request->secondary_gisid}",
                ], 422);
            }

            // ---------------------------------------------------------
            // NORMALISE TO MULTIPOLYGON
            // ---------------------------------------------------------

            $primaryMultiPolygon   = $this->toMultiPolygonCoordinates($primaryCoordinates);
            $secondaryMultiPolygon = $this->toMultiPolygonCoordinates($secondaryCoordinates);

            // Flatten one level — do NOT nest the two arrays
            $mergedCoordinates = array_merge($primaryMultiPolygon, $secondaryMultiPolygon);

            if (empty($mergedCoordinates)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merged MultiPolygon coordinates are empty.',
                ], 422);
            }

            // ---------------------------------------------------------
            // SUM SQFEET OF BOTH POLYGONS
            // ---------------------------------------------------------

            $primarySqfeet   = (float) ($primaryPolygon->sqfeet ?? 0);
            $secondarySqfeet = (float) ($secondaryPolygon->sqfeet ?? 0);

            // If a manual sqfeet is passed in the request, use it as the primary's value
            if (!empty($request->sqfeet) && (float) $request->sqfeet > 0) {
                $primarySqfeet = (float) $request->sqfeet;
            }

            $totalSqfeet = $primarySqfeet + $secondarySqfeet;

            // ---------------------------------------------------------
            // TRANSACTION
            // ---------------------------------------------------------

            DB::beginTransaction();

            // Update primary polygon as MultiPolygon with summed sqfeet
            DB::table($polygonTable)
                ->where('gisid', $request->primary_gisid)
                ->update([
                    'type'        => 'MultiPolygon',
                    'coordinates' => json_encode($mergedCoordinates, JSON_UNESCAPED_UNICODE),
                    'sqfeet'      => (string) $totalSqfeet,
                    'updated_at'  => now(),
                ]);

            // Delete secondary point
            DB::table($pointTable)
                ->where('gisid', $request->secondary_gisid)
                ->delete();

            // Delete secondary polygon
            DB::table($polygonTable)
                ->where('gisid', $request->secondary_gisid)
                ->delete();

            DB::commit();

            // ---------------------------------------------------------
            // FETCH UPDATED PRIMARY
            // ---------------------------------------------------------

            $mergedPolygon = DB::table($polygonTable)
                ->where('gisid', $request->primary_gisid)
                ->first();

            $mergedPoint = DB::table($pointTable)
                ->where('gisid', $request->primary_gisid)
                ->first();

            return response()->json([
                'success'         => true,
                'message'         => "Polygon {$request->secondary_gisid} merged into {$request->primary_gisid} successfully.",
                'merge_allowed'   => true,
                'primary_gisid'   => $request->primary_gisid,
                'secondary_gisid' => $request->secondary_gisid,
                'type'            => 'MultiPolygon',
                'sqfeet'          => $totalSqfeet,
                'polygon'         => $mergedPolygon,
                'point'           => $mergedPoint,
            ], 200);
        } catch (\Throwable $e) {

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Merge Polygon Error: ' . $e->getMessage(), [
                'primary_gisid'   => $request->primary_gisid,
                'secondary_gisid' => $request->secondary_gisid,
                'ward_id'         => $user->ward_id ?? null,
            ]);

            return response()->json([
                'success'         => false,
                'merge_allowed'   => false,
                'message'         => $e->getMessage(),
                'line'            => $e->getLine(),
                'file'            => $e->getFile(),
            ], 500);
        }
    }
    protected function toMultiPolygonCoordinates(array $coordinates): array
    {
        if (empty($coordinates)) {
            return [];
        }

        $first = $coordinates[0] ?? null;

        // MultiPolygon: coordinates[0][0][0] is [x, y]
        // Polygon:      coordinates[0][0]    is [x, y]
        if (
            is_array($first) &&
            isset($first[0]) &&
            is_array($first[0]) &&
            isset($first[0][0]) &&
            is_array($first[0][0]) &&
            isset($first[0][0][0]) &&
            is_numeric($first[0][0][0])
        ) {
            // Already MultiPolygon
            return $coordinates;
        }

        // Polygon → wrap it
        return [$coordinates];
    }
}
