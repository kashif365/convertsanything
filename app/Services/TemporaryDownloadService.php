<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class TemporaryDownloadService
{
    public function storeBinary(string $contents, string $downloadName): array
    {
        $this->ensureDirectoryExists();

        $storedName = $this->buildStoredName($downloadName);
        $path = $this->storagePath($storedName);

        file_put_contents($path, $contents);

        return $this->payload($storedName, $downloadName);
    }

    public function storeFile(string $sourcePath, string $downloadName): array
    {
        $this->ensureDirectoryExists();

        $storedName = $this->buildStoredName($downloadName);
        $target = $this->storagePath($storedName);

        copy($sourcePath, $target);

        return $this->payload($storedName, $downloadName);
    }

    public function resolve(string $storedName): ?array
    {
        $storedName = basename($storedName);
        $path = $this->storagePath($storedName);

        if (! is_file($path)) {
            return null;
        }

        $parts = explode('__', $storedName, 2);
        $downloadName = $parts[1] ?? $storedName;

        return [
            'path' => $path,
            'download_name' => $downloadName,
        ];
    }

    private function payload(string $storedName, string $downloadName): array
    {
        return [
            'filename' => $downloadName,
            'downloadUrl' => route('downloads.show', ['file' => $storedName]),
            'previewUrl' => route('downloads.show', ['file' => $storedName, 'inline' => 1]),
        ];
    }

    private function buildStoredName(string $downloadName): string
    {
        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $downloadName) ?: 'download.bin';

        return Str::uuid()->toString().'__'.$safeName;
    }

    private function ensureDirectoryExists(): void
    {
        File::ensureDirectoryExists($this->basePath());
    }

    private function storagePath(string $file): string
    {
        return $this->basePath().DIRECTORY_SEPARATOR.$file;
    }

    private function basePath(): string
    {
        return storage_path('app/temp');
    }
}
