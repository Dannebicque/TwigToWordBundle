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
        $pendingRowspans = [];
        foreach ($element->getElementsByTagName('tr') as $tr) {
            $table->addRow();
            $column = 0;
            foreach ($tr->childNodes as $cell) {
                if (!$cell instanceof \DOMElement || !in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                    continue;
                }

                while (isset($pendingRowspans[$column])) {
                    $table->addCell(null, ['vMerge' => 'continue']);
                    if (--$pendingRowspans[$column] === 0) { unset($pendingRowspans[$column]); }
                    ++$column;
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

                $colspan = max(1, (int) ($cell->getAttribute('colspan') ?: 1));
                $rowspan = max(1, (int) ($cell->getAttribute('rowspan') ?: 1));
                if ($colspan > 1) { $cellStyle['gridSpan'] = $colspan; }
                if ($rowspan > 1) {
                    $cellStyle['vMerge'] = 'restart';
                    for ($i = 0; $i < $colspan; ++$i) { $pendingRowspans[$column + $i] = $rowspan - 1; }
                }
                $wordCell = $table->addCell($cellWidth, $cellStyle);
                $column += $colspan;
                $run = $wordCell->addTextRun();
                (new InlineTextRenderer())->render($cell, $run, $isHeader ? ['bold' => true] : [], $styles);
            }
            while (isset($pendingRowspans[$column])) {
                $table->addCell(null, ['vMerge' => 'continue']);
                if (--$pendingRowspans[$column] === 0) { unset($pendingRowspans[$column]); }
                ++$column;
            }
        }
    }
}
