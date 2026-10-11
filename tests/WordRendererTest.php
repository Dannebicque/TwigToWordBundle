<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use Davidannebicque\TwigToWordBundle\Word\ThemeRegistry;
use Davidannebicque\TwigToWordBundle\Word\WordRenderer;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class WordRendererTest extends TestCase
{
    public function testConfiguredDefaultThemeIsUsed(): void
    {
        $twig = new Environment(new ArrayLoader(['report' => '<p>{{ title }}</p>']));
        $renderer = new WordRenderer(
            $twig,
            new HtmlDocumentInterpreter(),
            new ThemeRegistry([
                'default' => ['document' => ['orientation' => 'portrait']],
                'landscape' => ['document' => ['orientation' => 'landscape']],
            ]),
            'landscape'
        );

        $word = $renderer->createFromTemplate('report', ['title' => 'Hello']);
        self::assertSame('landscape', $word->getSections()[0]->getStyle()->getOrientation());
    }
}
