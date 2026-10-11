<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Word;

use PhpOffice\PhpWord\PhpWord;

/**
 * Registers editable Word paragraph and character styles on a fresh document.
 * Themes are resolved before registration; no styles leak between documents.
 */
final class WordStyleRegistrar
{
    /** @param array<string, mixed> $styles */
    public function register(PhpWord $document, array $styles): void
    {
        foreach ($styles as $name => $definition) {
            if (!is_string($name) || !is_array($definition)) {
                continue;
            }
            $paragraph = $definition['paragraph'] ?? [];
            $font = $definition['font'] ?? [];
            if (!is_array($paragraph) || !is_array($font)) {
                throw new \InvalidArgumentException(sprintf('Invalid Word style "%s".', $name));
            }
            $document->addParagraphStyle($name, $paragraph);
            $document->addFontStyle($name.'_font', $this->fontOptions($font));
        }
    }

    /** @param array<string, mixed> $font @return array<string, mixed> */
    public function fontOptions(array $font): array
    {
        $options = array_intersect_key($font, array_flip(['name', 'size', 'color', 'bold', 'italic', 'underline', 'allCaps', 'smallCaps', 'highlightColor']));
        if (isset($options['color'])) {
            $options['color'] = ltrim((string) $options['color'], '#');
        }
        return $options;
    }
}
