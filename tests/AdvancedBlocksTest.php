<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PHPUnit\Framework\TestCase;

final class AdvancedBlocksTest extends TestCase
{
    public function testNestedListsDoNotDuplicateChildText(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<ul><li>Parent<ol><li>Child</li></ol></li><li>Next</li></ul>'
        );
        $elements = $word->getSections()[0]->getElements();
        self::assertCount(3, $elements);
        self::assertSame('Parent', $elements[0]->getTextObject()->getText());
        self::assertSame('Child', $elements[1]->getTextObject()->getText());
        self::assertSame(1, $elements[1]->getDepth());
        self::assertSame('Next', $elements[2]->getTextObject()->getText());
    }

    public function testTablePreservesInlineBoldAndHeader(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml(
            '<table><tr><th>Header</th><td data-docx-width="2500"><strong>Value</strong></td></tr></table>'
        );
        $table = $word->getSections()[0]->getElements()[0];
        $row = $table->getRows()[0];
        self::assertCount(2, $row->getCells());
        $run = $row->getCells()[1]->getElements()[0];
        self::assertTrue($run->getElements()[0]->getFontStyle()->isBold());
    }
}
