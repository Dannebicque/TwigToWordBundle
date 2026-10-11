<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Word;

final class ThemeRegistry
{
    /** @param array<string, array<string, mixed>> $themes */
    public function __construct(private readonly array $themes = []) {}

    /** @return array<string, mixed> */
    public function resolve(string $name): array
    {
        return $this->resolveRecursive($name, []);
    }

    /** @param list<string> $visited @return array<string, mixed> */
    private function resolveRecursive(string $name, array $visited): array
    {
        if (in_array($name, $visited, true)) {
            throw new \LogicException('Circular theme inheritance: '.implode(' -> ', [...$visited, $name]));
        }
        if (!isset($this->themes[$name])) {
            throw new \InvalidArgumentException(sprintf('Unknown Word theme "%s".', $name));
        }
        $theme = $this->themes[$name];
        $parent = $theme['extends'] ?? null;
        unset($theme['extends']);
        if (!is_string($parent)) {
            return $theme;
        }
        return array_replace_recursive($this->resolveRecursive($parent, [...$visited, $name]), $theme);
    }
}
