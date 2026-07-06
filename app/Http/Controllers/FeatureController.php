<?php

namespace App\Http\Controllers;

use App\Services\WardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use geoPHP;

class FeatureController extends Controller
{
    protected $wardService;

    public function __construct(
        WardService $wardService
    ) {
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
                    $result = $this->wardService->storeSingleLine([
                        'ward_id'   => $wardId,
                        'layer_type' => $layerType,
                        'feature'   => $request->feature
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
            'type' => 'required',
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

        $result = $this->wardService->deletePolygon([
            'ward_id' => $wardId,
            'gisid'   => $request->gisid,
        ]);

        return response()->json($result);
    }
}
