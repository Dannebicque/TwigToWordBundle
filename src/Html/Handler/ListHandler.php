<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Html\Handler;

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Style\ListItem;

final class ListHandler implements BlockHandlerInterface
{
    public function supports(\DOMElement $element): bool
    {
        return in_array(strtolower($element->tagName), ['ul', 'ol'], true);
    }

    public function render(\DOMElement $element, Section $section, array $styles): void
    {
        $this->renderList($element, $section, 0);
    }

    private function renderList(\DOMElement $list, Section $section, int $depth): void
    {
        $type = strtolower($list->tagName) === 'ol'
            ? ListItem::TYPE_NUMBER
            : ListItem::TYPE_BULLET_FILLED;

        foreach ($list->childNodes as $item) {
            if (!$item instanceof \DOMElement || strtolower($item->tagName) !== 'li') {
                continue;
            }

            $parts = [];
            foreach ($item->childNodes as $child) {
                if ($child instanceof \DOMElement && in_array(strtolower($child->tagName), ['ul', 'ol'], true)) {
                    continue;
                }
                $parts[] = $child->textContent;
            }
            $text = trim(implode('', $parts));
            if ($text !== '') {
                $section->addListItem($text, $depth, null, $type);
            }

            foreach ($item->childNodes as $child) {
                if ($child instanceof \DOMElement && in_array(strtolower($child->tagName), ['ul', 'ol'], true)) {
                    $this->renderList($child, $section, $depth + 1);
                }
            }
        }
    }
}
