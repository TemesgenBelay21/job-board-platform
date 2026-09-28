<?php

namespace App\Helpers;

use App\Core\App;

/**
 * Resume storage.
 *
 * Resumes live in storage/resumes, outside the public web root, and are only
 * ever handed out by a controller that has checked the requester's permission.
 * The database stores just the filename, so this class is the single place that
 * knows how to turn a filename into a path on disk.
 */
class Resumes
{
    /** Directory (relative to the project root) holding resume files. */
    const DIRECTORY = '/storage/resumes';

    /** MIME type to send for each stored extension. */
    private static $contentTypes = [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public static function directory()
    {
        return App::storagePath('resumes');
    }

    /**
     * Absolute path for a stored filename.
     *
     * The filename is sanitised first: a value containing a slash or .. could
     * otherwise walk out of the storage folder.
     */
    public static function path($filename)
    {
        return self::directory() . DIRECTORY_SEPARATOR . self::sanitize($filename);
    }

    /**
     * Reduce a stored filename to its bare name, rejecting traversal attempts.
     */
    public static function sanitize($filename)
    {
        $filename = basename((string) $filename);

        if ($filename === '' || $filename === '.' || $filename === '..') {
            return '';
        }

        // Only ever allow the random hex name plus a known extension.
        if (!preg_match('/^[A-Za-z0-9_-]+\.(pdf|docx)$/i', $filename)) {
            return '';
        }

        return $filename;
    }

    public static function exists($filename)
    {
        $safe = self::sanitize($filename);

        return $safe !== '' && is_file(self::directory() . DIRECTORY_SEPARATOR . $safe);
    }

    /** The extension, or an empty string when the name is not usable. */
    public static function extension($filename)
    {
        $safe = self::sanitize($filename);

        if ($safe === '') {
            return '';
        }

        return strtolower((string) pathinfo($safe, PATHINFO_EXTENSION));
    }

    public static function contentType($filename)
    {
        $extension = self::extension($filename);

        return isset(self::$contentTypes[$extension]) ? self::$contentTypes[$extension] : 'application/octet-stream';
    }

    /** Human readable size for the UI, e.g. "184 KB". */
    public static function size($filename)
    {
        $path = self::path($filename);

        if (!is_file($path)) {
            return '';
        }

        $bytes = filesize($path);

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return max(1, (int) round($bytes / 1024)) . ' KB';
    }

    /**
     * Stream a stored file to the browser as a download.
     *
     * The caller is responsible for the permission check; this method only
     * turns an already-authorised filename into a response.
     */
    public static function download($filename, $downloadName = null)
    {
        $safe = self::sanitize($filename);

        if ($safe === '' || !is_file(self::directory() . DIRECTORY_SEPARATOR . $safe)) {
            return false;
        }

        $path = self::directory() . DIRECTORY_SEPARATOR . $safe;

        if ($downloadName === null) {
            $downloadName = 'resume.' . self::extension($safe);
        }

        // Never let the browser treat a stored file as active content.
        header('Content-Type: ' . self::contentType($safe));
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Content-Security-Policy: default-src \'none\'; sandbox');
        header('Cache-Control: private, no-store');

        readfile($path);

        return true;
    }

    /**
     * Validate a resume upload and store it, deleting the previous file.
     *
     * @param string $field     key in $_FILES
     * @param bool   $required
     *
     * @return array{ok: bool, errors: string[], filename: string|null}
     */
    public static function storeUpload($field, $required = true)
    {
        $validation = Upload::validate(
            $field,
            App::config('uploads.resume_extensions', ['pdf', 'docx']),
            (int) App::config('uploads.resume_max_size', 2097152),
            $required
        );

        if (!$validation['ok']) {
            return ['ok' => false, 'errors' => $validation['errors'], 'filename' => null];
        }

        $file = $_FILES[$field];

        try {
            $filename = Upload::store($file['tmp_name'], self::directory(), $validation['extension']);
        } catch (\RuntimeException $exception) {
            error_log($exception->getMessage());

            return [
                'ok' => false,
                'errors' => ['The file could not be saved. Please try again.'],
                'filename' => null,
            ];
        }

        return ['ok' => true, 'errors' => [], 'filename' => $filename];
    }

    /** Delete a stored file, ignoring anything that is not one of ours. */
    public static function delete($filename)
    {
        $safe = self::sanitize($filename);

        if ($safe === '') {
            return false;
        }

        $path = self::directory() . DIRECTORY_SEPARATOR . $safe;

        if (is_file($path)) {
            return unlink($path);
        }

        return false;
    }
}
