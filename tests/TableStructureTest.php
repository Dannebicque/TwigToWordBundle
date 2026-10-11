<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PhpOffice\PhpWord\IOFactory;
use PHPUnit\Framework\TestCase;

final class TableStructureTest extends TestCase
{
    public function testSectionsAndNestedTableRowsDoNotDuplicateOuterRows(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<table><thead><tr><th>Header</th></tr></thead>'.
            '<tbody><tr><td>Outer<table><tr><td>Inner</td></tr></table></td></tr></tbody>'.
            '<tfoot><tr><td>Footer</td></tr></tfoot></table>'
        );
        $path = tempnam(sys_get_temp_dir(), 'word_table_rows_');
        self::assertNotFalse($path);
        try {
            IOFactory::createWriter($word, 'Word2007')->save($path);
            $zip = new \ZipArchive();
            self::assertTrue($zip->open($path) === true);
            try {
                $xml = $zip->getFromName('word/document.xml');
                self::assertIsString($xml);
                $dom = new \DOMDocument();
                self::assertTrue($dom->loadXML($xml, LIBXML_NONET));
                $xp = new \DOMXPath($dom);
                $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                self::assertSame(3, $xp->query('//w:body/w:tbl/w:tr')->length);
                self::assertSame(1, $xp->query('//w:body/w:tbl/w:tr/w:tc/w:tbl')->length);
                self::assertSame(1, $xp->query('//w:body/w:tbl/w:tr/w:tc/w:tbl/w:tr')->length);
                self::assertSame(1, $xp->query('//w:t[text()="Inner"]')->length);
                self::assertSame(1, $xp->query('//w:t[text()="Header"]')->length);
                self::assertSame(1, $xp->query('//w:t[text()="Footer"]')->length);
            } finally { $zip->close(); }
        } finally { unlink($path); }
    }
}
