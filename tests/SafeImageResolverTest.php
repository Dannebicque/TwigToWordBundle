<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\SafeImageResolver;
use PHPUnit\Framework\TestCase;

final class SafeImageResolverTest extends TestCase
{
    public function testRejectsRemoteDataAndTraversal(): void
    {
        $directory = sys_get_temp_dir();
        $resolver = new SafeImageResolver([$directory]);
        self::assertNull($resolver->resolve('https://example.org/logo.png'));
        self::assertNull($resolver->resolve('data:image/png;base64,AAAA'));
        self::assertNull($resolver->resolve('../etc/passwd'));
        self::assertNull($resolver->resolve('/etc/passwd'));
        self::assertNull($resolver->resolve('nested/../logo.png'));
        self::assertNull($resolver->resolve('php://filter/resource=secret'));
    }

    public function testAllowsRealPngInsideConfiguredDirectory(): void
    {
        $directory = sys_get_temp_dir().'/twig_word_img_'.bin2hex(random_bytes(5));
        self::assertTrue(mkdir($directory));
        $path = $directory.'/logo.png';
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/nwAAAABJRU5ErkJggg=='));
        try {
            self::assertSame(realpath($path), (new SafeImageResolver([$directory]))->resolve('logo.png'));
            self::assertNull((new SafeImageResolver([$directory], 1))->resolve('logo.png'));
        } finally {
            unlink($path);
            rmdir($directory);
        }
    }
}
