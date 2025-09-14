<?php

namespace App\DTOs;

use Carbon\Carbon;
use JsonSerializable;

class ComponentDTO implements JsonSerializable
{
    public string $name;
    public string $view;
    public string $scss;
    public string $js;
    public ?string $version;
    public ?Carbon $cachedAt;
    public array $metadata;

    public function __construct(
        string $name,
        string $view = '',
        string $scss = '',
        string $js = '',
        ?string $version = null,
        ?Carbon $cachedAt = null,
        array $metadata = []
    ) {
        $this->name = $name;
        $this->view = $view;
        $this->scss = $scss;
        $this->js = $js;
        $this->version = $version ?? '1.0.0';
        $this->cachedAt = $cachedAt ?? now();
        $this->metadata = $metadata;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            view: $data['view'] ?? '',
            scss: $data['scss'] ?? '',
            js: $data['js'] ?? '',
            version: $data['version'] ?? null,
            cachedAt: isset($data['cachedAt']) ? Carbon::parse($data['cachedAt']) : null,
            metadata: $data['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'view' => $this->view,
            'scss' => $this->scss,
            'js' => $this->js,
            'version' => $this->version,
            'cachedAt' => $this->cachedAt?->toISOString(),
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function hasView(): bool
    {
        return !empty($this->view);
    }

    public function hasStyles(): bool
    {
        return !empty($this->scss);
    }

    public function hasScript(): bool
    {
        return !empty($this->js);
    }

    public function isValid(): bool
    {
        return !empty($this->name) && ($this->hasView() || $this->hasStyles() || $this->hasScript());
    }

    public function getCacheAge(): ?int
    {
        if (!$this->cachedAt) {
            return null;
        }

        return $this->cachedAt->diffInSeconds(now());
    }

    public function isStale(int $maxAge = 86400): bool
    {
        $age = $this->getCacheAge();
        
        return $age === null || $age > $maxAge;
    }

    public function getAssetHash(): string
    {
        return md5($this->scss . $this->js);
    }

    public function getFullHtml(array $data = []): string
    {
        $html = $this->view;

        foreach ($data as $key => $value) {
            $html = str_replace("{{ $key }}", htmlspecialchars($value), $html);
            $html = str_replace("{{{ $key }}}", $value, $html);
        }

        return $html;
    }

    public function getInlineStyles(): string
    {
        if (empty($this->scss)) {
            return '';
        }

        return "<style data-component=\"{$this->name}\">\n{$this->scss}\n</style>";
    }

    public function getInlineScript(): string
    {
        if (empty($this->js)) {
            return '';
        }

        return "<script data-component=\"{$this->name}\">\n{$this->js}\n</script>";
    }

    public function withMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }

    public function getMetadata(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->metadata;
        }

        return $this->metadata[$key] ?? $default;
    }
}