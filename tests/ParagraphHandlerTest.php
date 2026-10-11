<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\Handler\ParagraphHandler;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\TestCase;

final class ParagraphHandlerTest extends TestCase
{
    public function testParagraphWithInlineFormatting(): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML('<p>Hello <strong>world</strong></p>');
        $section = (new PhpWord())->addSection();
        (new ParagraphHandler())->render($dom->documentElement, $section, []);
        self::assertCount(1, $section->getElements());
        self::assertCount(2, $section->getElements()[0]->getElements());
    }
}
