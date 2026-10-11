<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PhpOffice\PhpWord\IOFactory;
use PHPUnit\Framework\TestCase;

final class DocxOutputTest extends TestCase
{
    public function testGeneratedDocxContainsNamedStylesAndText(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<h1 data-docx-style="ReportTitle">Rapport</h1><p>Bonjour</p>',
            ['styles' => [
                'ReportTitle' => [
                    'paragraph' => ['alignment' => 'center'],
                    'font' => ['bold' => true, 'size' => 22, 'color' => '17365D'],
                ],
            ]]
        );

        $path = tempnam(sys_get_temp_dir(), 'word_test_');
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

                $stylesXml = new \DOMDocument();
                self::assertTrue($stylesXml->loadXML($styles, LIBXML_NONET));
                $documentXml = new \DOMDocument();
                self::assertTrue($documentXml->loadXML($document, LIBXML_NONET));

                $xpath = new \DOMXPath($stylesXml);
                $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                self::assertGreaterThan(0, $xpath->query('//w:style[@w:styleId="ReportTitle"]')->length);
                // Font and paragraph formatting must be in the SAME native paragraph style.
                self::assertSame(1, $xpath->query('//w:style[@w:styleId="ReportTitle" and @w:type="paragraph"]/w:rPr/w:b')->length);
                self::assertSame(1, $xpath->query('//w:style[@w:styleId="ReportTitle"]/w:rPr/w:color[@w:val="17365D"]')->length);
                self::assertSame(1, $xpath->query('//w:style[@w:styleId="ReportTitle"]/w:pPr/w:jc[@w:val="center"]')->length);

                $xpath = new \DOMXPath($documentXml);
                $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                self::assertGreaterThan(0, $xpath->query('//w:pStyle[@w:val="ReportTitle"]')->length);
                self::assertGreaterThan(0, $xpath->query('//w:t[text()="Rapport"]')->length);
                self::assertSame(0, $xpath->query('//w:r[w:t="Rapport"]/w:rPr/w:b')->length);
                self::assertSame(0, $xpath->query('//w:r[w:t="Rapport"]/w:rPr/w:color')->length);
            } finally {
                $zip->close();
            }
        } finally {
            unlink($path);
        }
    }
}
