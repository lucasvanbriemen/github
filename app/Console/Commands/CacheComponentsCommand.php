<?php

namespace App\Console\Commands;

use App\Services\ComponentService;
use Illuminate\Console\Command;

class CacheComponentsCommand extends Command
{
    protected $signature = 'components:cache 
                            {name? : Component name to cache} 
                            {--clear : Clear cache before caching}
                            {--force : Force refresh cached components}
                            {--list : List available components}
                            {--health : Check component service health}';

    protected $description = 'Cache components from external API';

    protected ComponentService $componentService;

    public function __construct(ComponentService $componentService)
    {
        parent::__construct();
        $this->componentService = $componentService;
    }

    public function handle(): int
    {
        if ($this->option('health')) {
            return $this->checkHealth();
        }

        if ($this->option('list')) {
            return $this->listComponents();
        }

        if ($this->option('clear')) {
            $this->clearCache();
        }

        $name = $this->argument('name');
        $force = $this->option('force');

        if ($name) {
            return $this->cacheSingleComponent($name, $force);
        }

        return $this->cacheAllComponents($force);
    }

    protected function cacheSingleComponent(string $name, bool $force = false): int
    {
        $this->info("Caching component: {$name}");

        $component = $this->componentService->fetchComponent($name, $force);

        if (!$component) {
            $this->error("Failed to fetch component: {$name}");
            return 1;
        }

        $this->info("✓ Successfully cached component: {$name}");
        $this->line("  Version: {$component->version}");
        $this->line("  Has view: " . ($component->hasView() ? 'Yes' : 'No'));
        $this->line("  Has styles: " . ($component->hasStyles() ? 'Yes' : 'No'));
        $this->line("  Has script: " . ($component->hasScript() ? 'Yes' : 'No'));

        return 0;
    }

    protected function cacheAllComponents(bool $force = false): int
    {
        $this->info('Fetching available components...');
        
        $availableComponents = $this->componentService->listAvailableComponents();
        
        if (empty($availableComponents)) {
            $this->warn('No components available to cache');
            return 1;
        }

        $this->info("Found " . count($availableComponents) . " components to cache");

        $bar = $this->output->createProgressBar(count($availableComponents));
        $bar->start();

        $cached = [];
        $failed = [];

        foreach ($availableComponents as $name) {
            $component = $this->componentService->fetchComponent($name, $force);
            
            if ($component) {
                $cached[] = $name;
            } else {
                $failed[] = $name;
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Successfully cached " . count($cached) . " components");
        
        if (!empty($failed)) {
            $this->error("✗ Failed to cache " . count($failed) . " components:");
            foreach ($failed as $name) {
                $this->line("  - {$name}");
            }
            return 1;
        }

        $this->table(['Component', 'Status'], array_map(function ($name) {
            return [$name, '✓ Cached'];
        }, $cached));

        return 0;
    }

    protected function clearCache(): void
    {
        $this->info('Clearing component cache...');
        
        $result = $this->componentService->clearCache();
        
        if ($result) {
            $this->info('✓ Component cache cleared successfully');
        } else {
            $this->error('✗ Failed to clear component cache');
        }
    }

    protected function listComponents(): int
    {
        $this->info('Fetching available components...');
        
        $components = $this->componentService->listAvailableComponents();
        
        if (empty($components)) {
            $this->warn('No components available');
            return 1;
        }

        $this->info("Available components (" . count($components) . "):");
        
        $tableData = [];
        foreach ($components as $name) {
            $component = $this->componentService->fetchComponent($name);
            $status = $component ? '✓ Cached' : '○ Not cached';
            $version = $component ? $component->version : '-';
            
            $tableData[] = [$name, $status, $version];
        }

        $this->table(['Component', 'Status', 'Version'], $tableData);

        return 0;
    }

    protected function checkHealth(): int
    {
        $this->info('Checking component service health...');
        
        $health = $this->componentService->healthCheck();
        
        $status = $health['status'];
        $this->line("Status: " . ($status === 'healthy' ? '✓ Healthy' : '✗ ' . ucfirst($status)));
        $this->line("API URL: {$health['api_url']}");
        
        if (isset($health['response_time'])) {
            $this->line("Response time: " . number_format($health['response_time'] * 1000, 2) . "ms");
        }
        
        $this->line("Cached components: {$health['cached_components']}");
        
        if (isset($health['error'])) {
            $this->error("Error: {$health['error']}");
            return 1;
        }

        return $status === 'healthy' ? 0 : 1;
    }
}