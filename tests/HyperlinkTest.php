<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PHPUnit\Framework\TestCase;

final class HyperlinkTest extends TestCase
{
    public function testHttpLinkBecomesEditableWordLink(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml('<p>Read <a href="https://example.org">more</a></p>');
        $run = $word->getSections()[0]->getElements()[0];
        self::assertCount(2, $run->getElements());
        self::assertInstanceOf(\PhpOffice\PhpWord\Element\Link::class, $run->getElements()[1]);
    }

    public function testUnsafeSchemeDoesNotCreateLink(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml('<p><a href="javascript:alert(1)">No</a></p>');
        $run = $word->getSections()[0]->getElements()[0];
        self::assertInstanceOf(\PhpOffice\PhpWord\Element\Text::class, $run->getElements()[0]);
    }
}
