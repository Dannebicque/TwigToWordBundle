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
            '<div data-docx-section-break data-docx-orientation="landscape" data-docx-margin-left="900" data-docx-margin-right="900" data-docx-header-text="Landscape results" data-docx-footer-text="Confidential"><h2>Wide table</h2></div>'.
            '<p>Continuation in landscape</p>'.
            '<div data-docx-section-break data-docx-orientation="portrait"><p>Annex</p></div>'
        );
        self::assertCount(3, $word->getSections());
        self::assertSame(900, $word->getSections()[1]->getStyle()->getMarginLeft());
        self::assertSame(900, $word->getSections()[1]->getStyle()->getMarginRight());
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
                $headers = [];
                $footers = [];
                for ($i = 1; $i <= 4; ++$i) {
                    $header = $zip->getFromName('word/header'.$i.'.xml');
                    $footer = $zip->getFromName('word/footer'.$i.'.xml');
                    if (is_string($header)) { $headers[] = $header; }
                    if (is_string($footer)) { $footers[] = $footer; }
                }
                self::assertStringContainsString('Landscape results', implode('', $headers));
                self::assertStringContainsString('Confidential', implode('', $footers));
            } finally {
                $zip->close();
            }
        } finally {
            unlink($file);
        }
    }

    public function testRejectsInvalidSectionMargin(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new HtmlDocumentInterpreter())->fromHtml('<div data-docx-section-break data-docx-margin-left="-20">Invalid</div>');
    }

    public function testRejectsUnknownOrientation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new HtmlDocumentInterpreter())->fromHtml(
            '<div data-docx-section-break data-docx-orientation="diagonal">Bad</div>'
        );
    }
}
