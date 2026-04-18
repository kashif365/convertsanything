<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;

class ImageToolService
{
    private readonly ImageManager $images;

    public function __construct(
        private readonly TemporaryDownloadService $downloads,
    ) {
        $this->images = new ImageManager(new Driver());
    }

    public function jpgToPng(array $files, int $scale = 100): array
    {
        $downloads = [];

        foreach ($files as $file) {
            $image      = $this->readImage($file);
            $image      = $this->applyScale($image, $scale);
            $encoded    = $image->toPng();
            $sourceSize = $this->bytes($file->getSize() ?: 0);
            $outputSize = $this->bytes($encoded->size());

            $item          = $this->storeEncoded($encoded, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.png');
            $item['note']  = sprintf('%s converted to PNG.', $file->getClientOriginalName());
            $item['sizeLabel'] = sprintf('%s → %s', $sourceSize, $outputSize);
            $downloads[]   = $item;
        }

        return $this->response('Images converted to PNG.', $downloads);
    }

    public function pngToWebp(array $files, int $quality, int $scale = 100): array
    {
        $downloads = [];

        foreach ($files as $file) {
            $image      = $this->readImage($file);
            $image      = $this->applyScale($image, $scale);
            $encoded    = $image->toWebp($quality);
            $fallback   = $image->toPng();
            $sourceSize = $this->bytes($file->getSize() ?: 0);
            $outputSize = $this->bytes($encoded->size());

            $item = $this->storeEncoded($encoded, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.webp');
            $item['fallback']  = $this->storeEncoded($fallback, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '-fallback.png');
            $item['note']      = sprintf('%s converted to WebP at %d%% quality.', $file->getClientOriginalName(), $quality);
            $item['sizeLabel'] = sprintf('%s → %s', $sourceSize, $outputSize);
            $downloads[]       = $item;
        }

        return $this->response('Images converted to WebP.', $downloads);
    }

    public function compress(array $files, int $quality, string $format, int $scale = 100): array
    {
        $downloads = [];

        foreach ($files as $file) {
            $image        = $this->readImage($file);
            $image        = $this->applyScale($image, $scale);
            $targetFormat = $this->resolveCompressionFormat($file, $format);
            $encoded      = $this->encodeByFormat($image, $targetFormat, $quality);
            $sourceSize   = $this->bytes($file->getSize() ?: 0);
            $outputSize   = $this->bytes($encoded->size());

            $item = $this->storeEncoded($encoded, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.' . $this->extensionForFormat($targetFormat));

            if ($targetFormat === 'image/webp') {
                $item['fallback'] = $this->storeEncoded($image->toPng(), pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '-fallback.png');
            }

            $item['note']      = sprintf('%s compressed successfully.', $file->getClientOriginalName());
            $item['sizeLabel'] = sprintf('%s → %s', $sourceSize, $outputSize);
            $downloads[]       = $item;
        }

        return $this->response('Images compressed successfully.', $downloads);
    }

    public function resize(array $files, int $width, int $height, bool $lockAspect, int $scale = 100): array
    {
        $downloads = [];

        foreach ($files as $file) {
            $image = $this->readImage($file);

            if ($scale !== 100) {
                $image = $this->applyScale($image, $scale);
            }

            $processed  = $lockAspect ? $image->scale($width, $height) : $image->resize($width, $height);
            $format     = $this->normalizeMime($file->getMimeType() ?: 'image/jpeg');
            $encoded    = $this->encodeByFormat($processed, $format, 92);
            $sourceSize = $this->bytes($file->getSize() ?: 0);
            $outputSize = $this->bytes($encoded->size());

            $item          = $this->storeEncoded($encoded, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.' . $this->extensionForFormat($format));
            $item['note']  = sprintf('%s resized to %dx%d.', $file->getClientOriginalName(), $processed->width(), $processed->height());
            $item['sizeLabel'] = sprintf('%s → %s', $sourceSize, $outputSize);
            $downloads[]   = $item;
        }

        return $this->response('Images resized successfully.', $downloads);
    }

    public function convertAny(array $files, string $format, int $quality, int $scale = 100): array
    {
        $downloads = [];

        foreach ($files as $file) {
            $image        = $this->readImage($file);
            $image        = $this->applyScale($image, $scale);
            $targetFormat = $this->normalizeTargetFormat($format);
            $encoded      = $this->encodeByFormat($image, $targetFormat, $quality);
            $sourceSize   = $this->bytes($file->getSize() ?: 0);
            $outputSize   = $this->bytes($encoded->size());

            $item          = $this->storeEncoded($encoded, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.' . $this->extensionForFormat($targetFormat));
            $item['note']  = sprintf('%s converted to %s.', $file->getClientOriginalName(), strtoupper($this->extensionForFormat($targetFormat)));
            $item['sizeLabel'] = sprintf('%s → %s', $sourceSize, $outputSize);
            $downloads[]   = $item;
        }

        return $this->response('Images converted successfully.', $downloads);
    }

    private function applyScale(ImageInterface $image, int $scale): ImageInterface
    {
        if ($scale === 100 || $scale <= 0) {
            return $image;
        }

        $newWidth  = (int) max(1, round($image->width()  * $scale / 100));
        $newHeight = (int) max(1, round($image->height() * $scale / 100));

        return $image->scale($newWidth, $newHeight);
    }

    private function response(string $message, array $downloads): array
    {
        $first = $downloads[0] ?? ['filename' => null, 'downloadUrl' => null];

        return [
            'success'     => true,
            'message'     => $message,
            'filename'    => $first['filename'],
            'downloadUrl' => $first['downloadUrl'],
            'downloads'   => $downloads,
        ];
    }

    private function readImage(UploadedFile $file): ImageInterface
    {
        return $this->images->read($file->getRealPath());
    }

    private function encodeByFormat(ImageInterface $image, string $format, int $quality): EncodedImageInterface
    {
        return match ($format) {
            'image/png'  => $image->toPng(),
            'image/webp' => $image->toWebp($quality),
            default      => $image->toJpeg($quality),
        };
    }

    private function storeEncoded(EncodedImageInterface $encoded, string $downloadName): array
    {
        return $this->downloads->storeBinary($encoded->toString(), $downloadName);
    }

    private function resolveCompressionFormat(UploadedFile $file, string $format): string
    {
        if ($format !== 'auto') {
            return $format;
        }

        return $this->normalizeMime($file->getMimeType() ?: 'image/jpeg');
    }

    private function normalizeMime(string $mime): string
    {
        return match ($mime) {
            'image/jpg' => 'image/jpeg',
            default     => $mime,
        };
    }

    private function normalizeTargetFormat(string $format): string
    {
        return match ($format) {
            'image/png'  => 'image/png',
            'image/webp' => 'image/webp',
            default      => 'image/jpeg',
        };
    }

    private function extensionForFormat(string $format): string
    {
        return match ($format) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
    }

    private function bytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1024 / 1024, 1) . ' MB';
    }
}
