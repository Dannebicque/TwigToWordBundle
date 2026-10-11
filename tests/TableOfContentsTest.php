<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PhpOffice\PhpWord\IOFactory;
use PHPUnit\Framework\TestCase;

final class TableOfContentsTest extends TestCase
{
    public function testNativeTableOfContentsIsSerialized(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<div data-docx-toc data-docx-toc-min="1" data-docx-toc-max="3"></div><h1>Introduction</h1>'
        );
        $path = tempnam(sys_get_temp_dir(), 'word_toc_');
        self::assertNotFalse($path);
        try {
            IOFactory::createWriter($word, 'Word2007')->save($path);
            $zip = new \ZipArchive();
            self::assertTrue($zip->open($path) === true);
            try {
                $xml = $zip->getFromName('word/document.xml');
                self::assertIsString($xml);
                self::assertStringContainsString('TOC', $xml);
                self::assertStringContainsString('Introduction', $xml);
            } finally {
                $zip->close();
            }
        } finally {
            unlink($path);
        }
    }

    public function testRejectsInvalidHeadingRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new HtmlDocumentInterpreter())->fromHtml('<div data-docx-toc data-docx-toc-min="5" data-docx-toc-max="2"></div>');
    }
}
