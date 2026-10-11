<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PhpOffice\PhpWord\IOFactory;
use PHPUnit\Framework\TestCase;

final class MultipleSectionsTest extends TestCase
{
    public function testPortraitAndLandscapeSectionsAreExported(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<h1>Introduction</h1>'.
            '<div data-docx-section-break data-docx-orientation="landscape"><h2>Wide table</h2></div>'.
            '<p>Continuation in landscape</p>'.
            '<div data-docx-section-break data-docx-orientation="portrait"><p>Annex</p></div>'
        );
        self::assertCount(3, $word->getSections());
        $file = tempnam(sys_get_temp_dir(), 'word_sections_');
        self::assertNotFalse($file);
        try {
            IOFactory::createWriter($word, 'Word2007')->save($file);
            $zip = new \ZipArchive();
            self::assertTrue($zip->open($file) === true);
            try {
                $xml = $zip->getFromName('word/document.xml');
                self::assertIsString($xml);
                self::assertStringContainsString('landscape', $xml);
                self::assertStringContainsString('Wide table', $xml);
                self::assertStringContainsString('Continuation in landscape', $xml);
                self::assertStringContainsString('Annex', $xml);
            } finally {
                $zip->close();
            }
        } finally {
            unlink($file);
        }
    }

    public function testRejectsUnknownOrientation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new HtmlDocumentInterpreter())->fromHtml(
            '<div data-docx-section-break data-docx-orientation="diagonal">Bad</div>'
        );
    }
}
