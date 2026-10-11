<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Word\StyleResolver;
use PHPUnit\Framework\TestCase;

final class StyleResolverTest extends TestCase
{
    public function testLocalOverrides(): void
    {
        $resolver = new StyleResolver();
        $theme = ['styles' => ['title' => ['font' => ['bold' => true, 'size' => 22]]]];
        self::assertSame(['bold' => true, 'size' => 28], $resolver->resolve($theme, 'title', ['font' => ['size' => 28]])['font']);
    }

    public function testFontColorNormalization(): void
    {
        self::assertSame(['color' => 'ABCDEF'], (new StyleResolver())->fontStyle(['color' => '#ABCDEF']));
    }
}
