<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Html;

/**
 * Only resolves images inside explicitly configured local directories.
 * Never fetches remote resources or decodes inline data URIs.
 */
final class SafeImageResolver
{
    /** @param list<string> $allowedDirectories */
    public function __construct(
        private readonly array $allowedDirectories = [],
        private readonly int $maxBytes = 5242880,
    ) {}

    public function snapshot(string $source): ?string
    {
        $path = $this->resolve($source);
        if ($path === null) { return null; }
        $handle = @fopen($path, 'rb');
        if ($handle === false) { return null; }
        $temporary = tempnam(sys_get_temp_dir(), 'twig_word_image_');
        if ($temporary === false) { fclose($handle); return null; }
        try {
            $output = fopen($temporary, 'wb');
            if ($output === false) { return null; }
            try {
                $copied = stream_copy_to_stream($handle, $output, $this->maxBytes + 1);
            } finally {
                fclose($output);
            }
            if ($copied === false || $copied < 1 || $copied > $this->maxBytes) { return null; }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporary);
            if (!in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) { return null; }
            $info = @getimagesize($temporary);
            if ($info === false || $info[0] < 1 || $info[1] < 1 || $info[0] > 10000 || $info[1] > 10000) { return null; }
            return $temporary;
        } finally {
            fclose($handle);
            if (isset($temporary) && is_file($temporary) && (!isset($copied) || $copied === false || $copied < 1 || $copied > $this->maxBytes || !isset($mime) || !in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true) || !isset($info) || $info === false)) {
                @unlink($temporary);
            }
        }
    }

    public function resolve(string $source): ?string
    {
        if ($source === '' || str_contains($source, "\0") || str_contains($source, '?') || str_contains($source, '#')) {
            return null;
        }
        if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $source) || str_starts_with($source, '//')) {
            return null;
        }
        if (str_contains($source, '\\')) {
            return null;
        }

        if (str_starts_with($source, '/') || preg_match('~(^|/)\\.\\.?(/|$)~', $source)) {
            return null;
        }

        foreach ($this->allowedDirectories as $directory) {
            $base = realpath($directory);
            if ($base === false || !is_dir($base)) {
                continue;
            }
            $relative = $source;
            $file = realpath($base.DIRECTORY_SEPARATOR.$relative);
            if ($file === false || !str_starts_with($file, $base.DIRECTORY_SEPARATOR) || !is_file($file) || !is_readable($file)) {
                continue;
            }
            $size = filesize($file);
            if ($size === false || $size < 1 || $size > $this->maxBytes) {
                continue;
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
            if (!in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) {
                continue;
            }
            $info = @getimagesize($file);
            if ($info === false || $info[0] < 1 || $info[1] < 1 || $info[0] > 10000 || $info[1] > 10000) {
                continue;
            }
            return $file;
        }
        return null;
    }
}
