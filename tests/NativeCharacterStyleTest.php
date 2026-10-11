<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PhpOffice\PhpWord\IOFactory;
use Davidannebicque\TwigToWordBundle\Word\CharacterStyleDocxFixer;
use PHPUnit\Framework\TestCase;

final class NativeCharacterStyleTest extends TestCase
{
    public function testInlineSpanReferencesEditableWordCharacterStyle(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<p>Normal <span data-docx-style="Important">important</span> text.</p>',
            ['styles' => ['Important' => ['type' => 'character', 'font' => ['bold' => true, 'color' => 'C00000']]]]
        );
        $path = tempnam(sys_get_temp_dir(), 'word_char_');
        self::assertNotFalse($path);
        try {
            IOFactory::createWriter($word, 'Word2007')->save($path);
            (new CharacterStyleDocxFixer())->fix($path);
            $zip = new \ZipArchive();
            self::assertTrue($zip->open($path) === true);
            try {
                $styles = $zip->getFromName('word/styles.xml');
                $document = $zip->getFromName('word/document.xml');
                self::assertIsString($styles);
                self::assertIsString($document);
                $styleXml = new \DOMDocument();
                self::assertTrue($styleXml->loadXML($styles, LIBXML_NONET));
                $docXml = new \DOMDocument();
                self::assertTrue($docXml->loadXML($document, LIBXML_NONET));
                $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
                $xp = new \DOMXPath($styleXml);
                $xp->registerNamespace('w', $ns);
                self::assertSame(1, $xp->query('//w:style[@w:styleId="Important" and @w:type="character"]/w:rPr/w:b')->length);
                self::assertSame(1, $xp->query('//w:style[@w:styleId="Important"]/w:rPr/w:color[@w:val="C00000"]')->length);
                $xp = new \DOMXPath($docXml);
                $xp->registerNamespace('w', $ns);
                self::assertSame(1, $xp->query('//w:r[w:t="important"]/w:rPr/w:rStyle[@w:val="Important"]')->length);
                self::assertSame(0, $xp->query('//w:r[w:t="important"]/w:rPr/w:b')->length);
            } finally {
                $zip->close();
            }
        } finally {
            unlink($path);
        }
    }

    public function testNestedInlineFormattingRetainsStyleForUnmodifiedText(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<p><span data-docx-style="Important">Before <em>emphasis</em> after</span></p>',
            ['styles' => ['Important' => ['type' => 'character', 'font' => ['color' => 'C00000']]]]
        );
        $path = tempnam(sys_get_temp_dir(), 'word_nested_');
        self::assertNotFalse($path);
        try {
            IOFactory::createWriter($word, 'Word2007')->save($path);
            (new CharacterStyleDocxFixer())->fix($path);
            $zip = new \ZipArchive();
            self::assertTrue($zip->open($path) === true);
            try {
                $xml = $zip->getFromName('word/document.xml');
                self::assertIsString($xml);
                $dom = new \DOMDocument();
                self::assertTrue($dom->loadXML($xml, LIBXML_NONET));
                $xp = new \DOMXPath($dom);
                $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                self::assertSame(1, $xp->query('//w:r[w:t="Before "]/w:rPr/w:rStyle[@w:val="Important"]')->length);
                self::assertSame(1, $xp->query('//w:r[w:t=" after"]/w:rPr/w:rStyle[@w:val="Important"]')->length);
                self::assertSame(1, $xp->query('//w:r[w:t="emphasis"]/w:rPr/w:i')->length);
                self::assertSame(1, $xp->query('//w:r[w:t="emphasis"]/w:rPr/w:color[@w:val="C00000"]')->length);
            } finally {
                $zip->close();
            }
        } finally {
            unlink($path);
        }
    }

    public function testCharacterStyleCannotContainParagraphOptions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new HtmlDocumentInterpreter())->fromHtml('<p>Hi</p>', [
            'styles' => ['Bad' => ['type' => 'character', 'paragraph' => ['alignment' => 'center']]]
        ]);
    }
}
