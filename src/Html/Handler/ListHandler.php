<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Html\Handler;
use PhpOffice\PhpWord\Element\Section;
final class ListHandler implements BlockHandlerInterface
{
    public function supports(\DOMElement $element): bool
    {
        return in_array(strtolower($element->tagName), ['ul', 'ol'], true);
    }
    public function render(\DOMElement $element, Section $section, array $styles): void
    {
        $type = strtolower($element->tagName) === 'ol' ? 'decimal' : 'bullet';
        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement && strtolower($child->tagName) === 'li') {
                $section->addListItem(trim($child->textContent), 0, null, $type);
            }
        }
    }
}
