<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PHPUnit\Framework\TestCase;

final class HandlerIntegrationTest extends TestCase
{
    public function testDispatchesBlocksInOrder(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<h1>Heading</h1><p>Hello <strong>world</strong></p><ul><li>First</li></ul><table><tr><td>A</td></tr></table>'
        );
        self::assertCount(4, $word->getSections()[0]->getElements());
    }

    public function testDoesNotIncludeScriptContent(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml('<script>alert(1)</script><p>Visible</p>');
        self::assertCount(1, $word->getSections()[0]->getElements());
    }

    public function testInlineOverride(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml('<p><span data-docx-font-bold="true">Important</span></p>');
        $run = $word->getSections()[0]->getElements()[0];
        self::assertTrue($run->getElements()[0]->getFontStyle()->isBold());
    }
}
