<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;

class PhotoService
{
    private const DISK = 'public';

    private const MAX_DIMENSION = 1200;

    private const THUMB_DIMENSION = 300;

    private const QUALITY = 85;

    /**
     * Store a resized image and its thumbnail, returning their storage paths.
     *
     * @return array{photo_path: string, thumb_path: string}
     */
    public function store(UploadedFile $file): array
    {
        $extension = $this->extensionFor($file);
        $base = Str::uuid()->toString();

        $photoPath = "items/{$base}.{$extension}";
        $thumbPath = "items/thumbs/{$base}.{$extension}";

        $manager = new ImageManager(new Driver);
        $source = $file->getRealPath();

        $photo = $manager->read($source)
            ->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION);

        $thumb = $manager->read($source)
            ->scaleDown(width: self::THUMB_DIMENSION, height: self::THUMB_DIMENSION);

        Storage::disk(self::DISK)->put($photoPath, (string) $this->encode($photo, $extension));
        Storage::disk(self::DISK)->put($thumbPath, (string) $this->encode($thumb, $extension));

        return [
            'photo_path' => $photoPath,
            'thumb_path' => $thumbPath,
        ];
    }

    /**
     * Delete the given storage paths, ignoring missing or empty values.
     */
    public function delete(?string ...$paths): void
    {
        foreach ($paths as $path) {
            if (filled($path)) {
                Storage::disk(self::DISK)->delete($path);
            }
        }
    }

    /**
     * Encode an image using the supplied extension.
     */
    private function encode(Image $image, string $extension): EncodedImageInterface
    {
        return match ($extension) {
            'png' => $image->toPng(),
            'webp' => $image->toWebp(self::QUALITY),
            default => $image->toJpeg(self::QUALITY),
        };
    }

    /**
     * Determine the stored file extension for an upload.
     */
    private function extensionFor(UploadedFile $file): string
    {
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());

        return match ($extension) {
            'png' => 'png',
            'webp' => 'webp',
            default => 'jpg',
        };
    }
}
