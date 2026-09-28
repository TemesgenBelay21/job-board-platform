<?php

namespace App\Helpers;

/**
 * Secure file upload handling.
 *
 * The rules enforced here, in order:
 *   1. the request actually carried a file and PHP accepted it
 *   2. the size is within the configured limit
 *   3. the extension is on an allow list
 *   4. the MIME type reported by the file's own contents is on an allow list
 *   5. the leading bytes match the expected magic signature
 *
 * The name is never taken from the client. The stored file gets a random name,
 * and the original name is only used to decide the extension.
 */
class Upload
{
    /** Magic bytes we accept, checked against the start of the file. */
    private static $signatures = [
        'pdf' => ['%PDF'],
        'docx' => ['PK', "\x50\x4b\x03\x04"],
        'jpg' => ["\xFF\xD8\xFF"],
        'jpeg' => ["\xFF\xD8\xFF"],
        'png' => ["\x89PNG\r\n\x1A\n"],
        'gif' => ['GIF87a', 'GIF89a'],
        'webp' => ['RIFF'],
    ];

    /** MIME types accepted for each extension. */
    private static $mimeTypes = [
        'pdf' => ['application/pdf'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-word.document.macroenabled.12',
            'application/zip',
            'application/octet-stream',
        ],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp', 'image/x-webp'],
    ];

    /**
     * Validate an uploaded file from $_FILES.
     *
     * @param string $field      key in $_FILES
     * @param array  $allowed    allowed extensions, e.g. ['pdf', 'docx']
     * @param int    $maxSize    maximum size in bytes
     * @param bool   $required   whether an empty field is an error
     *
     * @return array{ok: bool, provided: bool, errors: string[], extension: string|null, size: int}
     */
    public static function validate($field, array $allowed, $maxSize, $required = true)
    {
        // 'provided' is false when an optional field was left empty. That case is
        // ok only because the caller marked the field optional, and it is
        // reported separately from a failure: callers must not treat a
        // deliberately skipped upload as a validation error.
        $result = ['ok' => false, 'provided' => false, 'errors' => [], 'extension' => null, 'size' => 0];

        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            if ($required) {
                $result['errors'][] = 'Please choose a file to upload.';
            } else {
                $result['ok'] = true;
            }

            return $result;
        }

        $file = $_FILES[$field];

        // An empty optional field is not an error and not an upload either.
        if (($file['error'] ?? null) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                $result['errors'][] = 'Please choose a file to upload.';
            } else {
                $result['ok'] = true;
            }

            return $result;
        }

        // These four keys are always present for a real upload.
        if (!isset($file['error']) || is_array($file['error'])) {
            $result['errors'][] = 'The upload was malformed. Please try again.';

            return $result;
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                $result['provided'] = true;
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $result['errors'][] = 'That file is larger than the ' . self::readableSize($maxSize) . ' limit.';

                return $result;
            case UPLOAD_ERR_PARTIAL:
                $result['errors'][] = 'The upload was interrupted. Please try again.';

                return $result;
            default:
                $result['errors'][] = 'The file could not be uploaded.';

                return $result;
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            $result['errors'][] = 'The file could not be verified as an upload.';

            return $result;
        }

        $size = (int) filesize($file['tmp_name']);
        $result['size'] = $size;

        if ($size === 0) {
            $result['errors'][] = 'That file is empty.';

            return $result;
        }

        if ($maxSize > 0 && $size > $maxSize) {
            $result['errors'][] = 'That file is larger than the ' . self::readableSize($maxSize) . ' limit.';

            return $result;
        }

        $extension = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = array_map('strtolower', $allowed);

        if ($extension === '' || !in_array($extension, $allowed, true)) {
            $result['errors'][] = 'Only ' . self::humanList($allowed) . ' files are accepted.';

            return $result;
        }

        $detected = self::detectMimeType($file['tmp_name']);

        $permittedMimes = isset(self::$mimeTypes[$extension]) ? self::$mimeTypes[$extension] : [];

        if ($detected === null || !in_array($detected, $permittedMimes, true)) {
            $result['errors'][] = 'That file is not a valid ' . strtoupper($extension) . ' file.';

            return $result;
        }

        // Last line of defence: a file whose contents do not start with the
        // expected signature is rejected even if the extension and MIME agree.
        if (!self::hasExpectedSignature($file['tmp_name'], $extension)) {
            $result['errors'][] = 'That file appears to be corrupt or is not really a '
                . strtoupper($extension) . ' file.';

            return $result;
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $imageInfo = @getimagesize($file['tmp_name']);

            if ($imageInfo === false) {
                $result['errors'][] = 'That image could not be read.';

                return $result;
            }
        }

        $result['ok'] = true;
        $result['extension'] = $extension;

        return $result;
    }

    /**
     * Move a validated upload to its final location with a random name.
     *
     * @param string $source     temporary path from $_FILES
     * @param string $directory  absolute destination directory
     * @param string $extension  already validated extension
     *
     * @return string The generated filename
     */
    public static function store($source, $directory, $extension)
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Could not create the upload directory: ' . $directory);
        }

        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $name;

        // move_uploaded_file additionally verifies the file came through PHP's
        // upload handler, which rename() does not.
        if (!move_uploaded_file($source, $destination)) {
            throw new \RuntimeException('Could not store the uploaded file.');
        }

        @chmod($destination, 0644);

        return $name;
    }

    /**
     * Read the MIME type from the file's contents, never from its name.
     */
    public static function detectMimeType($path)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo !== false) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);

                if ($mime !== false) {
                    return $mime;
                }
            }
        }

        return null;
    }

    private static function hasExpectedSignature($path, $extension)
    {
        if (!isset(self::$signatures[$extension])) {
            return true;
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $head = (string) fread($handle, 8);
        fclose($handle);

        foreach (self::$signatures[$extension] as $signature) {
            if (strncmp($head, $signature, strlen($signature)) === 0) {
                return true;
            }
        }

        return false;
    }

    private static function readableSize($bytes)
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024) . ' KB';
        }

        return $bytes . ' bytes';
    }

    private static function humanList(array $items)
    {
        $items = array_map('strtoupper', $items);

        if (count($items) === 1) {
            return $items[0];
        }

        $last = array_pop($items);

        return implode(', ', $items) . ' or ' . $last;
    }
}
