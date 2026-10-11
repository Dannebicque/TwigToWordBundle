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
        $styleName = $element->getAttribute('data-docx-style');
        if ($styleName !== '' && ($styles[$styleName]['type'] ?? null) !== 'table') {
            throw new \InvalidArgumentException(sprintf('Unknown Word table style "%s".', $styleName));
        }
        $table = $section->addTable($styleName !== '' ? $styleName : null);
        // Column-indexed spans carried over from previous rows.
        // Each span is represented once at its starting column.
        $pendingRowspans = [];
        foreach ($this->directRows($element) as $tr) {
            $table->addRow();
            $incoming = $pendingRowspans;
            $pendingRowspans = [];
            $column = 0;
            $emitContinuation = function (int $start, array $span) use ($table, &$pendingRowspans): void {
                $style = ['vMerge' => 'continue'];
                if ($span['width'] > 1) {
                    $style['gridSpan'] = $span['width'];
                }
                $table->addCell(null, $style);
                if ($span['remaining'] > 1) {
                    $pendingRowspans[$start] = ['width' => $span['width'], 'remaining' => $span['remaining'] - 1];
                }
            };
            foreach ($tr->childNodes as $cell) {
                if (!$cell instanceof \DOMElement || !in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                    continue;
                }
                while (isset($incoming[$column])) {
                    $span = $incoming[$column];
                    $emitContinuation($column, $span);
                    $column += $span['width'];
                    unset($incoming[$column - $span['width']]);
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
                    $pendingRowspans[$column] = ['width' => $colspan, 'remaining' => $rowspan - 1];
                }
                $wordCell = $table->addCell($cellWidth, $cellStyle);
                $column += $colspan;
                $run = $wordCell->addTextRun();
                (new InlineTextRenderer())->render($cell, $run, $isHeader ? ['bold' => true] : [], $styles);
            }
            ksort($incoming);
            foreach ($incoming as $start => $span) {
                if ($start < $column) {
                    throw new \InvalidArgumentException('Overlapping table row spans.');
                }
                $emitContinuation($start, $span);
                $column = $start + $span['width'];
            }
        }
    }

    /** @return list<\DOMElement> */
    private function directRows(\DOMElement $table): array
    {
        $rows = [];
        foreach ($table->childNodes as $child) {
            if (!$child instanceof \DOMElement) { continue; }
            $tag = strtolower($child->tagName);
            if ($tag === 'tr') { $rows[] = $child; continue; }
            if (!in_array($tag, ['thead', 'tbody', 'tfoot'], true)) { continue; }
            foreach ($child->childNodes as $row) {
                if ($row instanceof \DOMElement && strtolower($row->tagName) === 'tr') {
                    $rows[] = $row;
                }
            }
        }
        return $rows;
    }
}
