<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use Davidannebicque\TwigToWordBundle\Html\SafeImageResolver;
use PHPUnit\Framework\TestCase;

final class ImageLifecycleTest extends TestCase
{
    public function testSnapshotIsPrivateAndCleanedUp(): void
    {
        $directory = sys_get_temp_dir().'/twig_word_lifecycle_'.bin2hex(random_bytes(5));
        self::assertTrue(mkdir($directory));
        $source = $directory.'/logo.png';
        file_put_contents($source, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/nwAAAABJRU5ErkJggg=='));
        try {
            $resolver = new SafeImageResolver([$directory]);
            $snapshot = $resolver->snapshot('logo.png');
            self::assertNotNull($snapshot);
            self::assertFileExists($snapshot);
            self::assertNotSame(realpath($source), realpath($snapshot));
            self::assertSame(file_get_contents($source), file_get_contents($snapshot));
            self::assertSame(0600, fileperms($snapshot) & 0777);
            unlink($snapshot);

            $interpreter = new HtmlDocumentInterpreter($resolver);
            $document = $interpreter->fromHtml('<img src="logo.png">');
            $image = $document->getSections()[0]->getElements()[0];
            $temporary = $image->getSource();
            self::assertFileExists($temporary);
            $interpreter->cleanup($document);
            self::assertFileDoesNotExist($temporary);
            $interpreter->cleanup($document);
        } finally {
            unlink($source);
            rmdir($directory);
        }
    }
}
