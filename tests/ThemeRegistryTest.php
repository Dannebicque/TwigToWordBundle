<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Word\ThemeRegistry;
use PHPUnit\Framework\TestCase;

final class ThemeRegistryTest extends TestCase
{
    public function testInheritance(): void
    {
        $registry = new ThemeRegistry([
            'base' => ['styles' => ['title' => ['font' => ['size' => 20, 'bold' => true]]]],
            'custom' => ['extends' => 'base', 'styles' => ['title' => ['font' => ['size' => 26]]]],
        ]);
        self::assertSame(['size' => 26, 'bold' => true], $registry->resolve('custom')['styles']['title']['font']);
    }

    public function testCycleFails(): void
    {
        $this->expectException(\LogicException::class);
        (new ThemeRegistry(['a' => ['extends' => 'b'], 'b' => ['extends' => 'a']]))->resolve('a');
    }
}
