<?php

namespace App\Http\Controllers;

use App\Services\ComponentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ComponentController extends Controller
{
    protected ComponentService $componentService;

    public function __construct(ComponentService $componentService)
    {
        $this->componentService = $componentService;
    }

    public function index(): JsonResponse
    {
        $components = $this->componentService->listAvailableComponents();

        return response()->json([
            'success' => true,
            'data' => $components,
            'count' => count($components)
        ]);
    }

    public function show(string $name): JsonResponse
    {
        $component = $this->componentService->fetchComponent($name);

        if (!$component) {
            return response()->json([
                'success' => false,
                'message' => "Component '{$name}' not found"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $component->toArray()
        ]);
    }

    public function render(Request $request, string $name): Response
    {
        $data = $request->input('data', []);
        $html = $this->componentService->renderComponent($name, $data);

        if (!$html) {
            return response("Component '{$name}' not found", 404);
        }

        return response($html)->header('Content-Type', 'text/html');
    }

    public function assets(string $name): JsonResponse
    {
        $assets = $this->componentService->getComponentAssets($name);

        if (!$assets) {
            return response()->json([
                'success' => false,
                'message' => "Component '{$name}' not found"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $assets
        ]);
    }

    public function refresh(Request $request, string $name = null): JsonResponse
    {
        if ($name) {
            $component = $this->componentService->fetchComponent($name, true);
            
            if (!$component) {
                return response()->json([
                    'success' => false,
                    'message' => "Failed to refresh component '{$name}'"
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => "Component '{$name}' refreshed successfully",
                'data' => $component->toArray()
            ]);
        }

        $loaded = $this->componentService->preloadComponents();

        return response()->json([
            'success' => true,
            'message' => 'All components refreshed successfully',
            'loaded' => $loaded,
            'count' => count($loaded)
        ]);
    }

    public function clearCache(string $name = null): JsonResponse
    {
        $result = $this->componentService->clearCache($name);

        if ($name) {
            return response()->json([
                'success' => $result,
                'message' => $result 
                    ? "Cache cleared for component '{$name}'"
                    : "Failed to clear cache for component '{$name}'"
            ]);
        }

        return response()->json([
            'success' => $result,
            'message' => $result 
                ? 'All component caches cleared successfully'
                : 'Failed to clear component caches'
        ]);
    }

    public function health(): JsonResponse
    {
        $health = $this->componentService->healthCheck();

        return response()->json([
            'success' => $health['status'] === 'healthy',
            'data' => $health
        ], $health['status'] === 'healthy' ? 200 : 503);
    }

    public function batch(Request $request): JsonResponse
    {
        $request->validate([
            'components' => 'required|array',
            'components.*' => 'string'
        ]);

        $names = $request->input('components');
        $forceRefresh = $request->boolean('force_refresh', false);
        
        $components = $this->componentService->fetchMultipleComponents($names, $forceRefresh);

        return response()->json([
            'success' => true,
            'data' => $components,
            'requested' => count($names),
            'loaded' => count($components)
        ]);
    }

    public function inline(string $name): Response
    {
        $component = $this->componentService->fetchComponent($name);

        if (!$component) {
            return response("<!-- Component '{$name}' not found -->", 404);
        }

        $html = '';
        
        if ($component->hasStyles()) {
            $html .= $component->getInlineStyles() . "\n";
        }
        
        if ($component->hasView()) {
            $html .= $component->view . "\n";
        }
        
        if ($component->hasScript()) {
            $html .= $component->getInlineScript() . "\n";
        }

        return response($html)->header('Content-Type', 'text/html');
    }
}