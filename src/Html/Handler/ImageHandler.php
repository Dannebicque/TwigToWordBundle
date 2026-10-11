<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Html\Handler;

use Davidannebicque\TwigToWordBundle\Html\SafeImageResolver;
use PhpOffice\PhpWord\Element\Section;

final class ImageHandler implements BlockHandlerInterface
{
    public function __construct(private readonly SafeImageResolver $images) {}

    public function supports(\DOMElement $element): bool
    {
        return strtolower($element->tagName) === 'img';
    }

    public function render(\DOMElement $element, Section $section, array $styles): void
    {
        $path = $this->images->resolve(trim($element->getAttribute('src')));
        if ($path === null) {
            return;
        }

        $options = [];
        foreach (['width', 'height'] as $dimension) {
            $value = $element->getAttribute('data-docx-'.$dimension);
            if ($value !== '' && ctype_digit($value) && (int) $value >= 1 && (int) $value <= 2000) {
                $options[$dimension] = (int) $value;
            }
        }
        $section->addImage($path, $options);
    }
}
