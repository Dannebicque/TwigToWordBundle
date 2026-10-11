<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PhpOffice\PhpWord\IOFactory;
use PHPUnit\Framework\TestCase;

final class MergedTableDocxTest extends TestCase
{
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
