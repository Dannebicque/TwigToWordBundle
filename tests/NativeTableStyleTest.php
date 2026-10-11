<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PhpOffice\PhpWord\IOFactory;
use PHPUnit\Framework\TestCase;

final class NativeTableStyleTest extends TestCase
{
    public function testNamedTableStyleIsWrittenToDocx(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<table data-docx-style="ReportTable"><tr><th>Year</th><th>Value</th></tr><tr><td>2026</td><td>42</td></tr></table>',
            ['styles' => ['ReportTable' => ['type' => 'table', 'table' => ['borderSize' => 6, 'borderColor' => '4472C4'], 'first_row' => ['bgColor' => 'D9EAF7']]]]
        );
        $path = tempnam(sys_get_temp_dir(), 'word_table_style_');
        self::assertNotFalse($path);
        try {
            IOFactory::createWriter($word, 'Word2007')->save($path);
            $zip = new \ZipArchive();
            self::assertTrue($zip->open($path) === true);
            try {
                $styles = $zip->getFromName('word/styles.xml');
                $document = $zip->getFromName('word/document.xml');
                self::assertIsString($styles);
                self::assertIsString($document);
                $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
                $stylesXml = new \DOMDocument();
                self::assertTrue($stylesXml->loadXML($styles, LIBXML_NONET));
                $docXml = new \DOMDocument();
                self::assertTrue($docXml->loadXML($document, LIBXML_NONET));
                $xp = new \DOMXPath($stylesXml);
                $xp->registerNamespace('w', $ns);
                self::assertSame(1, $xp->query('//w:style[@w:type="table" and @w:styleId="ReportTable"]')->length);
                $xp = new \DOMXPath($docXml);
                $xp->registerNamespace('w', $ns);
                self::assertSame(1, $xp->query('//w:tbl/w:tblPr/w:tblStyle[@w:val="ReportTable"]')->length);
            } finally {
                $zip->close();
            }
        } finally {
            unlink($path);
        }
    }

    public function testUnknownTableStyleIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new HtmlDocumentInterpreter())->fromHtml('<table data-docx-style="Unknown"><tr><td>X</td></tr></table>');
    }
}
