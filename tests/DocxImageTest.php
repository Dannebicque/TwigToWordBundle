<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use Davidannebicque\TwigToWordBundle\Html\SafeImageResolver;
use PhpOffice\PhpWord\IOFactory;
use PHPUnit\Framework\TestCase;

final class DocxImageTest extends TestCase
{
    public function testEmbeddedImageHasMediaAndDocumentRelationship(): void
    {
        $directory = sys_get_temp_dir().'/twig_word_media_'.bin2hex(random_bytes(5));
        self::assertTrue(mkdir($directory));
        $image = $directory.'/logo.png';
        $docx = $directory.'/result.docx';
        file_put_contents($image, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/nwAAAABJRU5ErkJggg=='));
        try {
            $word = (new HtmlDocumentInterpreter(new SafeImageResolver([$directory])))->fromHtml(
                '<img src="logo.png" data-docx-width="120">'
            );
            IOFactory::createWriter($word, 'Word2007')->save($docx);
            $zip = new \ZipArchive();
            self::assertTrue($zip->open($docx) === true);
            try {
                $media = [];
                for ($i = 0; $i < $zip->numFiles; ++$i) {
                    $name = $zip->getNameIndex($i);
                    if (str_starts_with($name, 'word/media/')) {
                        $media[] = $name;
                    }
                }
                self::assertNotEmpty($media);
                $rels = $zip->getFromName('word/_rels/document.xml.rels');
                self::assertIsString($rels);
                self::assertStringContainsString('image', $rels);
                $document = $zip->getFromName('word/document.xml');
                self::assertIsString($document);
                self::assertStringContainsString('blip', $document);
            } finally {
                $zip->close();
            }
        } finally {
            @unlink($docx);
            @unlink($image);
            rmdir($directory);
        }
    }
}
