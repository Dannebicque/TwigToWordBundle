<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PhpOffice\PhpWord\IOFactory;
use PHPUnit\Framework\TestCase;

final class StyleRelationsTest extends TestCase
{
    public function testParagraphStyleRelationsAreSerialized(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<p data-docx-style="Caption">Caption text</p>',
            ['styles' => [
                'Base' => ['paragraph' => ['alignment' => 'left']],
                'Caption' => ['basedOn' => 'Base', 'next' => 'Base', 'paragraph' => ['spaceAfter' => 120]],
            ]]
        );
        $path = tempnam(sys_get_temp_dir(), 'word_relations_');
        self::assertNotFalse($path);
        try {
            IOFactory::createWriter($word, 'Word2007')->save($path);
            $zip = new \ZipArchive();
            self::assertTrue($zip->open($path) === true);
            try {
                $xml = $zip->getFromName('word/styles.xml');
                self::assertIsString($xml);
                $dom = new \DOMDocument();
                self::assertTrue($dom->loadXML($xml, LIBXML_NONET));
                $xp = new \DOMXPath($dom);
                $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                self::assertSame(1, $xp->query('//w:style[@w:styleId="Caption"]/w:basedOn[@w:val="Base"]')->length);
                self::assertSame(1, $xp->query('//w:style[@w:styleId="Caption"]/w:next[@w:val="Base"]')->length);
            } finally { $zip->close(); }
        } finally { unlink($path); }
    }

    public function testRejectsCircularInheritance(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new HtmlDocumentInterpreter())->fromHtml('<p>Test</p>', ['styles' => [
            'A' => ['basedOn' => 'B'], 'B' => ['basedOn' => 'A'],
        ]]);
    }

    public function testRejectsUnknownParentStyle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new HtmlDocumentInterpreter())->fromHtml('<p>Test</p>', ['styles' => [
            'A' => ['basedOn' => 'Missing'],
        ]]);
    }
}
