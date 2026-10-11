<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Word;

use PhpOffice\PhpWord\Element\Section;

/**
 * Render simple, editable headers and footers configured by a Word theme.
 */
final class HeaderFooterRenderer
{
    /** @param array<string, mixed> $theme */
    public function apply(Section $section, array $theme): void
    {
        foreach (['header', 'footer'] as $part) {
            $config = $theme[$part] ?? null;
            if (!is_array($config)) {
                continue;
            }

            $text = $config['text'] ?? '';
            $pageNumbers = $config['page_numbers'] ?? false;
            if (!is_string($text) || !is_bool($pageNumbers)) {
                throw new \InvalidArgumentException(sprintf('Invalid %s configuration.', $part));
            }
            if ($text === '' && !$pageNumbers) {
                continue;
            }

            $container = $part === 'header' ? $section->addHeader() : $section->addFooter();
            $alignment = $config['alignment'] ?? 'left';
            if (!in_array($alignment, ['left', 'center', 'right'], true)) {
                throw new \InvalidArgumentException(sprintf('Invalid %s alignment.', $part));
            }
            $paragraph = ['alignment' => $alignment];
            $run = $container->addTextRun($paragraph);
            if ($text !== '') {
                $run->addText($text);
            }
            if ($pageNumbers) {
                if ($text !== '') {
                    $run->addText(' ');
                }
                $run->addField('PAGE');
            }
        }
    }
}
