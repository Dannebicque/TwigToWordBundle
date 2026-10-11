<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use Davidannebicque\TwigToWordBundle\Word\ThemeRegistry;
use Davidannebicque\TwigToWordBundle\Word\WordRenderer;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class CombinedStyleNextTest extends TestCase
{
    public function testCombinedFontAndParagraphStyleHasNextRelationInSavedDocx(): void
    {
        $renderer = new WordRenderer(
            new Environment(new ArrayLoader([])),
            new HtmlDocumentInterpreter(),
            new ThemeRegistry(['default' => ['styles' => [
                'Base' => ['paragraph' => ['alignment' => 'left']],
                'Caption' => ['font' => ['bold' => true, 'color' => '17365D'], 'basedOn' => 'Base', 'next' => 'Base'],
            ]]])
        );
        $word = $renderer->createFromHtml('<p data-docx-style="Caption">Caption</p>');
        $path = tempnam(sys_get_temp_dir(), 'word_next_');
        self::assertNotFalse($path);
        try {
            $renderer->save($word, $path);
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
                self::assertSame(1, $xp->query('//w:style[@w:styleId="Caption"]/w:rPr/w:b')->length);
            } finally { $zip->close(); }
        } finally { unlink($path); }
    }
}
