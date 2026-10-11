<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PHPUnit\Framework\TestCase;

final class HtmlDocumentInterpreterTest extends TestCase
{
    public function testRendersParagraphAndTable(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<h1>Report</h1><p>Hello <strong>world</strong></p><table><tr><td>A</td></tr></table>'
        );

        self::assertCount(3, $word->getSections()[0]->getElements());
    }

    public function testLocalFontOverride(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<p><span data-docx-font-bold="true">Important</span></p>'
        );
        $run = $word->getSections()[0]->getElements()[0];
        $text = $run->getElements()[0];
        self::assertTrue($text->getFontStyle()->isBold());
    }

    public function testSectionOptions(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<p>Test</p>',
            ['document' => ['orientation' => 'landscape']]
        );
        self::assertSame('landscape', $word->getSections()[0]->getStyle()->getOrientation());
    }
}
