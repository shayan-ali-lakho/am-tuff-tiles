<?php

declare(strict_types=1);

namespace App\Core;

use GdImage;
use RuntimeException;

/**
 * Safe handling of uploaded product photos.
 *
 * Every upload is checked (real file type, size, pixel count), decoded and RE-ENCODED as a new JPEG,
 * so nothing from the original file (hidden code, metadata) is ever stored. Files get random names;
 * the original file name is never used. Two files are written per photo:
 *   products/<random>.jpg         full size (longest side 1600 px)
 *   products/<random>_thumb.jpg   thumbnail (longest side 640 px)
 */
final class ImageUploader
{
    public const MAX_BYTES = 10 * 1024 * 1024;
    private const MAX_PIXELS = 40000000;
    private const FULL_SIZE = 1600;
    private const THUMB_SIZE = 640;
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Store one entry of $_FILES and return its path relative to public/uploads.
     *
     * @throws RuntimeException with a message that is safe to show to the admin
     */
    public static function store(array $file): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::uploadError($error));
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        if (!is_uploaded_file($tmp)) {
            throw new RuntimeException('The file was not uploaded correctly. Please try again.');
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new RuntimeException('The photo is larger than 10 MB.');
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);

        if (!in_array($mime, self::ALLOWED, true)) {
            throw new RuntimeException('Only JPG, PNG or WebP photos are allowed.');
        }

        if ($mime === 'image/webp' && !function_exists('imagecreatefromwebp')) {
            throw new RuntimeException('This server cannot read WebP photos. Please use a JPG or PNG instead.');
        }

        $info = @getimagesize($tmp);

        if ($info === false) {
            throw new RuntimeException('This file is not a valid image.');
        }

        [$width, $height] = $info;

        if ($width < 1 || $height < 1 || $width * $height > self::MAX_PIXELS) {
            throw new RuntimeException('The photo has too many pixels (about 40 megapixels at most). Please make it smaller.');
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png'  => @imagecreatefrompng($tmp),
            default      => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
        };

        if (!$source instanceof GdImage) {
            throw new RuntimeException('The photo could not be read. It may be damaged.');
        }

        if ($mime === 'image/jpeg') {
            $source = self::applyOrientation($source, $tmp);
        }

        $full  = self::fit($source, self::FULL_SIZE);
        $thumb = self::fit($full, self::THUMB_SIZE);

        $dir = self::uploadDir();

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('The uploads folder could not be created on the server.');
        }

        if (!is_writable($dir)) {
            throw new RuntimeException('The uploads folder is not writable on the server.');
        }

        $name     = bin2hex(random_bytes(12));
        $fullPath = $dir . '/' . $name . '.jpg';
        $thumbPath = $dir . '/' . $name . '_thumb.jpg';

        imageinterlace($full, true);

        if (!imagejpeg($full, $fullPath, 82) || !imagejpeg($thumb, $thumbPath, 80)) {
            @unlink($fullPath);
            @unlink($thumbPath);

            throw new RuntimeException('The photo could not be saved on the server.');
        }

        return 'products/' . $name . '.jpg';
    }

    /** Delete a stored photo and its thumbnail. Ignores anything that is not a path we created. */
    public static function remove(string $relativePath): void
    {
        if (preg_match('#^products/([a-f0-9]{24})\.jpg$#', $relativePath, $m) !== 1) {
            return;
        }

        $dir = self::uploadDir();
        @unlink($dir . '/' . $m[1] . '.jpg');
        @unlink($dir . '/' . $m[1] . '_thumb.jpg');
    }

    private static function uploadDir(): string
    {
        return BASE_PATH . '/public/uploads/products';
    }

    /**
     * Copy onto a new white canvas no larger than $max on the longest side (never enlarges).
     * White background means transparent PNGs become clean JPEGs.
     */
    private static function fit(GdImage $source, int $max): GdImage
    {
        $width  = imagesx($source);
        $height = imagesy($source);
        $scale  = min(1.0, $max / max($width, $height));

        $newWidth  = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $canvas;
    }

    /** Phone photos are often stored sideways with an EXIF flag; turn them upright. */
    private static function applyOrientation(GdImage $image, string $file): GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($file);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $angle = match ($orientation) {
            3       => 180,
            6       => -90,
            8       => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated instanceof GdImage ? $rotated : $image;
    }

    private static function uploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The photo is larger than the server allows.',
            UPLOAD_ERR_PARTIAL                        => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'The server could not receive the photo.',
            default                                   => 'The photo could not be uploaded.',
        };
    }
}
