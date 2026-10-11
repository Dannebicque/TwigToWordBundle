<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use Davidannebicque\TwigToWordBundle\Html\SafeImageResolver;
use PHPUnit\Framework\TestCase;

final class RenderingFailureCleanupTest extends TestCase
{
    public function testInvalidTocAfterImageDoesNotLeakSnapshots(): void
    {
        $directory = sys_get_temp_dir().'/twig_word_failure_'.bin2hex(random_bytes(5));
        self::assertTrue(mkdir($directory));
        $source = $directory.'/logo.png';
        file_put_contents($source, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/nwAAAABJRU5ErkJggg=='));
        $before = glob(sys_get_temp_dir().'/twig_word_image_*') ?: [];
        try {
            try {
                (new HtmlDocumentInterpreter(new SafeImageResolver([$directory])))->fromHtml(
                    '<img src="logo.png"><div data-docx-toc data-docx-toc-min="5" data-docx-toc-max="2"></div>'
                );
                self::fail('Expected invalid TOC heading range.');
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString('heading range', $exception->getMessage());
            }
            $after = glob(sys_get_temp_dir().'/twig_word_image_*') ?: [];
            self::assertSame([], array_values(array_diff($after, $before)));
        } finally {
            unlink($source);
            rmdir($directory);
        }
    }
}
