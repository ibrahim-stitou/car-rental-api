<?php

namespace App\Core\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

trait HasMediaCollections
{
    public function uploadMedia(UploadedFile $file, string $collection, ?string $name = null)
    {
        $adder = $this->addMedia($file)
            ->usingFileName(Str::uuid() . '.' . $file->getClientOriginalExtension());

        if ($name) {
            $adder->usingName($name);
        }

        return $adder->toMediaCollection($collection);
    }

    public function uploadMultipleMedia(array $files, string $collection, ?array $names = null): array
    {
        $media = [];
        foreach ($files as $index => $file) {
            $media[] = $this->uploadMedia($file, $collection, $names[$index] ?? null);
        }
        return $media;
    }

    public function getMediaByCollection(string $collection): array
    {
        return $this->getMedia($collection)->map(fn($media) => [
            'id'           => $media->id,
            'name'         => $media->name,
            'file_name'    => $media->file_name,
            'mime_type'    => $media->mime_type,
            'size'         => $media->size,
            'url'          => $media->getFullUrl(),
            'collection'   => $media->collection_name,
            'created_at'   => $media->created_at->toISOString(),
        ])->toArray();
    }

    public function getAllMediaFormatted(): array
    {
        $result = [];
        foreach ($this->getRegisteredMediaCollections() as $collection) {
            $result[$collection->name] = $this->getMediaByCollection($collection->name);
        }
        return $result;
    }
}

