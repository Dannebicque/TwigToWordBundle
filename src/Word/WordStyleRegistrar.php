<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Word;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style;

/**
 * Registers editable Word paragraph and character styles on a fresh document.
 * Themes are resolved before registration. PHPWord's static style registry
 * requires explicit isolation when creating successive documents.
 */
final class WordStyleRegistrar
{
    /**
     * PHPWord stores named styles in a process-wide registry. Resetting it
     * before each document prevents accidental theme carry-over in workers.
     */
    public function reset(): void
    {
        Style::resetStyles();
    }

    /** @param array<string, mixed> $styles */
    public function register(PhpWord $document, array $styles): void
    {
        $this->validateRelations($styles);
        foreach ($styles as $name => $definition) {
            if (!is_string($name) || !is_array($definition)) {
                continue;
            }
            $paragraph = $definition['paragraph'] ?? [];
            $font = $definition['font'] ?? [];
            if (!is_array($paragraph) || !is_array($font)) {
                throw new \InvalidArgumentException(sprintf('Invalid Word style "%s".', $name));
            }
            foreach (['basedOn', 'next'] as $relation) {
                if (isset($definition[$relation])) {
                    $paragraph[$relation] = $definition[$relation];
                }
            }
            $type = $definition['type'] ?? 'paragraph';
            if (!in_array($type, ['paragraph', 'character', 'table'], true)) {
                throw new \InvalidArgumentException(sprintf('Invalid Word style type for "%s".', $name));
            }
            if ($type === 'table') {
                $table = $definition['table'] ?? [];
                $firstRow = $definition['first_row'] ?? [];
                if (!is_array($table) || !is_array($firstRow) || $font !== [] || $paragraph !== []) {
                    throw new \InvalidArgumentException(sprintf('Invalid table style "%s".', $name));
                }
                $document->addTableStyle($name, $table, $firstRow);
                continue;
            }
            if ($type === 'character') {
                if ($paragraph !== []) {
                    throw new \InvalidArgumentException(sprintf('Character style "%s" cannot define paragraph properties.', $name));
                }
                $document->addFontStyle($name, $this->fontOptions($font));
                continue;
            }
            // A Font style with paragraph properties is serialized by PHPWord
            // as a single native Word paragraph style containing both w:pPr
            // and w:rPr. This makes typography editable through Word's styles.
            // Headings use title styles for table-of-contents integration.
            if (preg_match('/^Heading([1-6])$/', $name, $matches)) {
                $document->addTitleStyle((int) $matches[1], $this->fontOptions($font), $paragraph);
            } else {
                if ($font !== []) {
                    $document->addFontStyle($name, $this->fontOptions($font), $paragraph);
                } else {
                    $document->addParagraphStyle($name, $paragraph);
                }
            }
        }
    }

    /** @param array<string, mixed> $styles */
    private function validateRelations(array $styles): void
    {
        foreach ($styles as $name => $definition) {
            if (!is_string($name) || !is_array($definition)) { continue; }
            $type = $definition['type'] ?? 'paragraph';
            foreach (['basedOn', 'next'] as $relation) {
                $target = $definition[$relation] ?? ($definition['paragraph'][$relation] ?? null);
                if ($target === null) { continue; }
                if ($type !== 'paragraph' || !is_string($target) || $target === '') {
                    throw new \InvalidArgumentException(sprintf('Invalid %s relation for Word style "%s".', $relation, $name));
                }
                if ($target !== 'Normal' && !preg_match('/^Heading[1-6]$/', $target)
                    && (!isset($styles[$target]) || ($styles[$target]['type'] ?? 'paragraph') !== 'paragraph')) {
                    throw new \InvalidArgumentException(sprintf('Unknown paragraph style "%s" referenced by "%s".', $target, $name));
                }
            }
            $seen = [$name => true];
            $current = $name;
            while (true) {
                $style = $styles[$current] ?? [];
                $parent = $style['basedOn'] ?? ($style['paragraph']['basedOn'] ?? null);
                if ($parent === null || $parent === 'Normal' || !isset($styles[$parent])) { break; }
                if (isset($seen[$parent])) {
                    throw new \InvalidArgumentException(sprintf('Circular Word style inheritance involving "%s".', $name));
                }
                $seen[$parent] = true;
                $current = $parent;
            }
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
