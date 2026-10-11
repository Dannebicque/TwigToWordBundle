<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Html\Handler;

use PhpOffice\PhpWord\Element\Section;

final class TableHandler implements BlockHandlerInterface
{
    public function supports(\DOMElement $element): bool
    {
        return strtolower($element->tagName) === 'table';
    }

    public function render(\DOMElement $element, Section $section, array $styles): void
    {
        $table = $section->addTable();
        foreach ($element->getElementsByTagName('tr') as $tr) {
            $table->addRow();
            foreach ($tr->childNodes as $cell) {
                if (!$cell instanceof \DOMElement || !in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                    continue;
                }

                $isHeader = strtolower($cell->tagName) === 'th';
                $cellStyle = [];
                if ($isHeader) {
                    $cellStyle['bgColor'] = 'E8EDF3';
                }

                $width = $cell->getAttribute('data-docx-width');
                if ($width !== '' && ctype_digit($width)) {
                    $cellWidth = (int) $width;
                } else {
                    $cellWidth = null;
                }

                $wordCell = $table->addCell($cellWidth, $cellStyle);
                $run = $wordCell->addTextRun();
                (new InlineTextRenderer())->render($cell, $run, $isHeader ? ['bold' => true] : [], $styles);
            }
        }
    }
}
