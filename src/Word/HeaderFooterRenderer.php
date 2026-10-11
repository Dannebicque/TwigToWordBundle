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
            $totalPages = $config['total_pages'] ?? false;
            if (!is_string($text) || !is_bool($pageNumbers) || !is_bool($totalPages)) {
                throw new \InvalidArgumentException(sprintf('Invalid %s configuration.', $part));
            }
            if ($text === '' && !$pageNumbers && !$totalPages) {
                continue;
            }

            $firstPage = $config['first_page'] ?? false;
            if (!is_bool($firstPage)) {
                throw new \InvalidArgumentException(sprintf('Invalid %s first_page flag.', $part));
            }
            $container = $part === 'header'
                ? $section->addHeader($firstPage ? \PhpOffice\PhpWord\Element\Header::FIRST : \PhpOffice\PhpWord\Element\Header::AUTO)
                : $section->addFooter($firstPage ? \PhpOffice\PhpWord\Element\Footer::FIRST : \PhpOffice\PhpWord\Element\Footer::AUTO);
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
            if ($totalPages) {
                if ($text !== '' || $pageNumbers) {
                    $run->addText($pageNumbers ? ' / ' : ' ');
                }
                $run->addField('NUMPAGES');
            }
        }
    }
}
