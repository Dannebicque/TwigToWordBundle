<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Word;

final class StyleResolver
{
    /** @param array<string, mixed> $theme @return array<string, mixed> */
    public function resolve(array $theme, string $name, array $overrides = []): array
    {
        $styles = $theme['styles'] ?? [];
        $definition = $styles[$name] ?? [];
        if (!is_array($definition)) {
            throw new \InvalidArgumentException(sprintf('Invalid style "%s".', $name));
        }
        return array_replace_recursive($definition, $overrides);
    }

    /** @param array<string, mixed> $font @return array<string, mixed> */
    public function fontStyle(array $font): array
    {
        $result = [];
        foreach (['bold', 'italic', 'size', 'name', 'underline'] as $key) {
            if (array_key_exists($key, $font)) { $result[$key] = $font[$key]; }
        }
        if (isset($font['color'])) {
            $result['color'] = ltrim((string) $font['color'], '#');
        }
        return $result;
    }
}
