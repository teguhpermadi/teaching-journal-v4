<?php

namespace App\Filament\Infolists\Components;

use Closure;
use Filament\Infolists\Components\Entry;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ImageViewer extends Entry
{
    protected string $view = 'filament.infolists.components.image-viewer';

    protected array|Closure|null $images = null;

    protected string|Closure|null $collectionName = 'activity_photos';

    protected int|Closure|null $gridColumns = 3;

    protected string|Closure|null $height = '200px';

    protected bool|Closure $openOnClick = true;

    protected string|Closure|null $emptyStateMessage = 'Tidak ada foto';

    protected string|Closure|null $conversion = 'thumbnail';

    public function images(array|Closure|null $images): static
    {
        $this->images = $images;

        return $this;
    }

    public function collection(string|Closure|null $collection): static
    {
        $this->collectionName = $collection;

        return $this;
    }

    public function gridColumns(int|Closure|null $columns): static
    {
        $this->gridColumns = $columns;

        return $this;
    }

    public function height(string|Closure|null $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function openOnClick(bool|Closure $openOnClick = true): static
    {
        $this->openOnClick = $openOnClick;

        return $this;
    }

    public function emptyStateMessage(string|Closure|null $message): static
    {
        $this->emptyStateMessage = $message;

        return $this;
    }

    public function conversion(string|Closure|null $conversion): static
    {
        $this->conversion = $conversion;

        return $this;
    }

    public function getImages(): array
    {
        $images = $this->evaluate($this->images);

        if ($images instanceof Collection) {
            return $images->map(function ($media) {
                return $this->formatMediaItem($media);
            })->toArray();
        }

        if ($images instanceof Arrayable) {
            $images = $images->toArray();
        }

        // If images is explicitly set, return it
        if (is_array($images) && !empty($images)) {
            return $images;
        }

        // Otherwise, try to get from record
        $record = $this->getRecord();
        $collection = $this->getCollectionName();

        if ($record && method_exists($record, 'getMedia')) {
            $media = $record->getMedia($collection);

            if ($media->isNotEmpty()) {
                return $media->map(function ($item) {
                    return $this->formatMediaItem($item);
                })->toArray();
            }
        }

        return [];
    }

    public function getGridColumns(): int
    {
        return $this->evaluate($this->gridColumns) ?? 3;
    }

    public function getHeight(): string
    {
        return $this->evaluate($this->height) ?? '200px';
    }

    public function shouldOpenOnClick(): bool
    {
        return $this->evaluate($this->openOnClick);
    }

    public function getEmptyStateMessage(): string
    {
        return $this->evaluate($this->emptyStateMessage) ?? 'Tidak ada foto';
    }

    public function getCollectionName(): string
    {
        return $this->evaluate($this->collectionName) ?? 'activity_photos';
    }

    public function getConversion(): string
    {
        return $this->evaluate($this->conversion) ?? 'thumbnail';
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Don't use default() here, let getImages() handle it
        // This allows more flexibility in different contexts
    }

    protected function formatMediaItem($media): array
    {
        $conversion = $this->getConversion();
        
        // Handle both Media model and array
        if (is_array($media)) {
            return $media;
        }

        try {
            $data = [
                'id' => $media->id ?? null,
                'name' => $media->name ?? $media->file_name ?? 'Image',
                'url' => $media->getUrl(),
            ];

            // Add conversion URL if it exists
            if ($conversion && method_exists($media, 'hasGeneratedConversion') && $media->hasGeneratedConversion($conversion)) {
                $data['thumbnail'] = $media->getUrl($conversion);
            } else {
                $data['thumbnail'] = $data['url'];
            }

            // Add additional media properties
            $data['size'] = $media->size ?? 0;
            $data['mime_type'] = $media->mime_type ?? 'image/jpeg';
            $data['created_at'] = $media->created_at ?? now();

            return $data;
        } catch (\Exception $e) {
            // Fallback if getUrl() fails
            return [
                'id' => $media->id ?? null,
                'name' => $media->name ?? $media->file_name ?? 'Image',
                'url' => $media->original_url ?? '#',
                'thumbnail' => $media->original_url ?? '#',
                'size' => $media->size ?? 0,
                'mime_type' => $media->mime_type ?? 'image/jpeg',
                'created_at' => $media->created_at ?? now(),
            ];
        }
    }
}
