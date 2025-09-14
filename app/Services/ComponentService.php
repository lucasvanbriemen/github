<?php

namespace App\Services;

use App\DTOs\ComponentDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ComponentService
{
    protected string $apiUrl;
    protected int $cacheTtl;
    protected array $availableComponents;
    protected bool $fallbackEnabled;

    public function __construct()
    {
        $this->apiUrl = config('components.api_url', 'https://components.lucasvanbriemen.nl/api');
        $this->cacheTtl = config('components.cache_ttl', 86400);
        $this->availableComponents = config('components.available_components', []);
        $this->fallbackEnabled = config('components.fallback_enabled', true);
    }

    public function fetchComponent(string $name, bool $forceRefresh = false): ?ComponentDTO
    {
        $cacheKey = $this->getCacheKey($name);

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(10)
                ->retry(3, 100)
                ->get("{$this->apiUrl}/{$name}");

            if ($response->successful()) {
                $data = $response->json();
                $component = ComponentDTO::fromArray($data);
                
                Cache::put($cacheKey, $component, $this->cacheTtl);
                
                Log::info("Component fetched successfully", ['component' => $name]);
                
                return $component;
            }

            Log::warning("Failed to fetch component", [
                'component' => $name,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

        } catch (Exception $e) {
            Log::error("Error fetching component", [
                'component' => $name,
                'error' => $e->getMessage()
            ]);
        }

        if ($this->fallbackEnabled) {
            return $this->getFallbackComponent($name);
        }

        return null;
    }

    public function fetchMultipleComponents(array $names, bool $forceRefresh = false): array
    {
        $components = [];

        foreach ($names as $name) {
            if ($component = $this->fetchComponent($name, $forceRefresh)) {
                $components[$name] = $component;
            }
        }

        return $components;
    }

    public function listAvailableComponents(): array
    {
        if (!empty($this->availableComponents)) {
            return $this->availableComponents;
        }

        $cacheKey = 'components:list';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(10)->get($this->apiUrl);

            if ($response->successful()) {
                $components = $response->json();
                Cache::put($cacheKey, $components, $this->cacheTtl);
                return $components;
            }
        } catch (Exception $e) {
            Log::error("Error fetching component list", ['error' => $e->getMessage()]);
        }

        return [];
    }

    public function clearCache(?string $name = null): bool
    {
        if ($name) {
            return Cache::forget($this->getCacheKey($name));
        }

        foreach ($this->listAvailableComponents() as $component) {
            Cache::forget($this->getCacheKey($component));
        }

        Cache::forget('components:list');
        
        return true;
    }

    public function preloadComponents(): array
    {
        $components = $this->listAvailableComponents();
        $loaded = [];

        foreach ($components as $name) {
            if ($this->fetchComponent($name, true)) {
                $loaded[] = $name;
            }
        }

        return $loaded;
    }

    public function renderComponent(string $name, array $data = []): ?string
    {
        $component = $this->fetchComponent($name);

        if (!$component) {
            return null;
        }

        $html = $component->view;

        foreach ($data as $key => $value) {
            $html = str_replace("{{ $key }}", $value, $html);
        }

        return $html;
    }

    public function getComponentAssets(string $name): ?array
    {
        $component = $this->fetchComponent($name);

        if (!$component) {
            return null;
        }

        return [
            'css' => $component->scss,
            'js' => $component->js
        ];
    }

    protected function getCacheKey(string $name): string
    {
        $version = config('components.version', '1.0.0');
        return "component:{$name}:{$version}";
    }

    protected function getFallbackComponent(string $name): ?ComponentDTO
    {
        $fallbackPath = resource_path("components/{$name}.json");

        if (!file_exists($fallbackPath)) {
            return null;
        }

        try {
            $data = json_decode(file_get_contents($fallbackPath), true);
            return ComponentDTO::fromArray($data);
        } catch (Exception $e) {
            Log::error("Error loading fallback component", [
                'component' => $name,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    public function validateComponent(ComponentDTO $component): bool
    {
        return !empty($component->name) && 
               (!empty($component->view) || !empty($component->scss) || !empty($component->js));
    }

    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(5)->get($this->apiUrl);
            
            return [
                'status' => $response->successful() ? 'healthy' : 'unhealthy',
                'response_time' => $response->transferStats->getTransferTime(),
                'cached_components' => count($this->getCachedComponents()),
                'api_url' => $this->apiUrl
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'cached_components' => count($this->getCachedComponents()),
                'api_url' => $this->apiUrl
            ];
        }
    }

    protected function getCachedComponents(): array
    {
        $cached = [];
        $components = $this->listAvailableComponents();

        foreach ($components as $name) {
            if (Cache::has($this->getCacheKey($name))) {
                $cached[] = $name;
            }
        }

        return $cached;
    }
}