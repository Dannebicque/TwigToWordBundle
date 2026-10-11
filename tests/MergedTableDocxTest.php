<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PhpOffice\PhpWord\IOFactory;
use PHPUnit\Framework\TestCase;

final class MergedTableDocxTest extends TestCase
{
    public function testRowspanContinuationOccursOnlyOnFollowingRow(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<table><tr><td>A</td><td rowspan="2" colspan="2">B</td></tr>'.
            '<tr><td>C</td></tr><tr><td>D</td><td>E</td><td>F</td></tr></table>'
        );
        $file = tempnam(sys_get_temp_dir(), 'word_merge_');
        self::assertNotFalse($file);
        try {
            IOFactory::createWriter($word, 'Word2007')->save($file);
            $zip = new \\ZipArchive();
            self::assertTrue($zip->open($file) === true);
            try {
                $xml = $zip->getFromName('word/document.xml');
                self::assertIsString($xml);
                $dom = new \\DOMDocument();
                self::assertTrue($dom->loadXML($xml, LIBXML_NONET));
                $xpath = new \\DOMXPath($dom);
                $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                $rows = $xpath->query('//w:tbl/w:tr');
                self::assertCount(3, $rows);
                self::assertSame(1, $xpath->query('.//w:vMerge[@w:val="restart"]', $rows->item(0))->length);
                self::assertSame(0, $xpath->query('.//w:vMerge[@w:val="continue"]', $rows->item(0))->length);
                self::assertSame(1, $xpath->query('.//w:vMerge[@w:val="continue"]', $rows->item(1))->length);
                self::assertSame(1, $xpath->query('.//w:gridSpan[@w:val="2"]', $rows->item(1))->length);
                self::assertSame(0, $xpath->query('.//w:vMerge', $rows->item(2))->length);
            } finally {
                $zip->close();
            }
        } finally {
            unlink($file);
        }
    }

    public function testColspanAndRowspanAppearInDocxXml(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<table><tr><th colspan="2">Header</th></tr>'.
            '<tr><td rowspan="2">Group</td><td>First</td></tr>'.
            '<tr><td>Second</td></tr></table>'
        );
        $file = tempnam(sys_get_temp_dir(), 'word_merge_');
        self::assertNotFalse($file);
        try {
            IOFactory::createWriter($word, 'Word2007')->save($file);
            $zip = new \ZipArchive();
            self::assertTrue($zip->open($file) === true);
            try {
                $xml = $zip->getFromName('word/document.xml');
                self::assertIsString($xml);
                self::assertStringContainsString('w:gridSpan', $xml);
                self::assertStringContainsString('w:vMerge', $xml);
                self::assertStringContainsString('Group', $xml);
                self::assertStringContainsString('Second', $xml);
            } finally {
                $zip->close();
            }
        } finally {
            unlink($file);
        }
    }
}
