<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;

class TempFileService
{
    private const DISK = 'temp';
    private const MAX_AGE_HOURS = 24;

    public function store(UploadedFile $file): array
    {
        $id       = Str::uuid()->toString();
        $ext      = $file->getClientOriginalExtension();
        $filename = $id . ($ext ? ".{$ext}" : '');

        Storage::disk(self::DISK)->putFileAs('', $file, $filename);

        return [
            'id'            => $id,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'preview_url'   => route('files.temp.preview', ['id' => $id]),
        ];
    }

    public function storeMany(array $files): array
    {
        return array_map(fn ($file) => $this->store($file), $files);
    }

    public function path(string $id): ?string
    {
        $files = Storage::disk(self::DISK)->files();
        foreach ($files as $file) {
            if (str_starts_with(basename($file), $id)) {
                return Storage::disk(self::DISK)->path($file);
            }
        }
        return null;
    }

    public function moveToMedia(string $id, HasMedia $model, string $collection): bool
    {
        $path = $this->path($id);
        if (! $path || ! file_exists($path)) {
            return false;
        }

        $model->addMedia($path)
            ->usingFileName(basename($path))
            ->toMediaCollection($collection);

        return true;
    }

    public function delete(string $id): void
    {
        $files = Storage::disk(self::DISK)->files();
        foreach ($files as $file) {
            if (str_starts_with(basename($file), $id)) {
                Storage::disk(self::DISK)->delete($file);
                return;
            }
        }
    }

    public function deleteMany(array $ids): void
    {
        foreach ($ids as $id) {
            $this->delete($id);
        }
    }

    public function cleanupOldFiles(): int
    {
        $deleted  = 0;
        $cutoff   = now()->subHours(self::MAX_AGE_HOURS)->timestamp;
        $files    = Storage::disk(self::DISK)->files();

        foreach ($files as $file) {
            $lastModified = Storage::disk(self::DISK)->lastModified($file);
            if ($lastModified < $cutoff) {
                Storage::disk(self::DISK)->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    public function getAbsolutePath(string $id): ?string
    {
        return $this->path($id);
    }
}