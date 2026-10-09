<?php

declare(strict_types=1);

namespace App\Core;

use GdImage;
use RuntimeException;

/**
 * Safe handling of uploaded images (product photos and payment screenshots).
 *
 * Every upload is checked (real file type, size, pixel count), decoded and RE-ENCODED as a new JPEG,
 * so nothing from the original file (hidden code, metadata) is ever stored. Files get random names;
 * the original file name is never used.
 *
 * Product photos are public. Two files are written per photo:
 *   public/uploads/products/<random>.jpg         full size (longest side 1600 px)
 *   public/uploads/products/<random>_thumb.jpg   thumbnail (longest side 640 px)
 *
 * Payment screenshots are private. They are written OUTSIDE the public folder, one file each:
 *   storage/payment-proofs/<random>.jpg          (longest side 2000 px)
 * and are only ever sent to a logged-in admin through the order page.
 */
final class ImageUploader
{
    public const MAX_BYTES = 10 * 1024 * 1024;
    private const MAX_PIXELS = 40000000;
    private const FULL_SIZE = 1600;
    private const THUMB_SIZE = 640;
    private const PROOF_SIZE = 2000;
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Store one entry of $_FILES and return its path relative to public/uploads.
     *
     * @throws RuntimeException with a message that is safe to show to the admin
     */
    public static function store(array $file): string
    {
        $source = self::load($file, 'photo');

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

    /**
     * Store a customer's payment screenshot (one entry of $_FILES) in the private folder and return its
     * path relative to storage/, e.g. payment-proofs/<random>.jpg
     *
     * @throws RuntimeException with a message that is safe to show to the customer
     */
    public static function storeProof(array $file): string
    {
        $image = self::fit(self::load($file, 'screenshot'), self::PROOF_SIZE);

        $dir = self::proofDir();

        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException('Your screenshot could not be saved on the server. Please try again or call us.');
        }

        if (!is_writable($dir)) {
            throw new RuntimeException('Your screenshot could not be saved on the server. Please try again or call us.');
        }

        $name = bin2hex(random_bytes(12));

        if (!imagejpeg($image, $dir . '/' . $name . '.jpg', 85)) {
            @unlink($dir . '/' . $name . '.jpg');

            throw new RuntimeException('Your screenshot could not be saved on the server. Please try again or call us.');
        }

        return 'payment-proofs/' . $name . '.jpg';
    }

    /** Full path of a stored screenshot, or null when the value is not a path we created. */
    public static function proofPath(string $relativePath): ?string
    {
        if (preg_match('#^payment-proofs/([a-f0-9]{24})\.jpg$#', $relativePath, $m) !== 1) {
            return null;
        }

        return self::proofDir() . '/' . $m[1] . '.jpg';
    }

    /** Delete a stored screenshot (used when an order could not be saved). */
    public static function removeProof(string $relativePath): void
    {
        $path = self::proofPath($relativePath);

        if ($path !== null) {
            @unlink($path);
        }
    }

    private static function uploadDir(): string
    {
        return BASE_PATH . '/public/uploads/products';
    }

    private static function proofDir(): string
    {
        return BASE_PATH . '/storage/payment-proofs';
    }

    /**
     * Check one entry of $_FILES and decode it into an image (turned upright if it is a sideways phone photo).
     * $noun is the word used in the error messages: "photo" or "screenshot".
     *
     * @throws RuntimeException
     */
    private static function load(array $file, string $noun): GdImage
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::uploadError($error, $noun));
        }

        $tmp = $file['tmp_name'] ?? '';

        if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('The file was not uploaded correctly. Please try again.');
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new RuntimeException('The ' . $noun . ' is larger than 10 MB.');
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);

        if (!in_array($mime, self::ALLOWED, true)) {
            throw new RuntimeException('Only JPG, PNG or WebP ' . $noun . 's are allowed.');
        }

        if ($mime === 'image/webp' && !function_exists('imagecreatefromwebp')) {
            throw new RuntimeException('This server cannot read WebP ' . $noun . 's. Please use a JPG or PNG instead.');
        }

        $info = @getimagesize($tmp);

        if ($info === false) {
            throw new RuntimeException('This file is not a valid image.');
        }

        [$width, $height] = $info;

        if ($width < 1 || $height < 1 || $width * $height > self::MAX_PIXELS) {
            throw new RuntimeException('The ' . $noun . ' has too many pixels (about 40 megapixels at most). Please make it smaller.');
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png'  => @imagecreatefrompng($tmp),
            default      => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
        };

        if (!$source instanceof GdImage) {
            throw new RuntimeException('The ' . $noun . ' could not be read. It may be damaged.');
        }

        if ($mime === 'image/jpeg') {
            $source = self::applyOrientation($source, $tmp);
        }

        return $source;
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

    private static function uploadError(int $code, string $noun): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The ' . $noun . ' is larger than the server allows.',
            UPLOAD_ERR_PARTIAL                        => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'The server could not receive the ' . $noun . '.',
            default                                   => 'The ' . $noun . ' could not be uploaded.',
        };
    }
}
