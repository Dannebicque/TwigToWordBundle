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
                if (!$cell instanceof \DOMElement || !in_array(strtolower($cell->tagName), ['td', 'th'], true)) { continue; }
                $table->addCell()->addText(trim($cell->textContent));
            }
        }
    }
}
