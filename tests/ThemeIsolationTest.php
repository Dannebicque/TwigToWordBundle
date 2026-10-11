<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PhpOffice\PhpWord\Style;
use PHPUnit\Framework\TestCase;

final class ThemeIsolationTest extends TestCase
{
    public function testStylesDoNotCarryOverBetweenSequentialDocuments(): void
    {
        $interpreter = new HtmlDocumentInterpreter();
        $interpreter->fromHtml('<p>First</p>', [
            'styles' => ['ThemeOnly' => ['font' => ['size' => 24]]],
        ]);
        self::assertNotNull(Style::getStyle('ThemeOnly'));

        $interpreter->fromHtml('<p>Second</p>', [
            'styles' => ['OtherTheme' => ['font' => ['size' => 10]]],
        ]);
        self::assertNull(Style::getStyle('ThemeOnly'));
        self::assertNotNull(Style::getStyle('OtherTheme'));
    }
}
