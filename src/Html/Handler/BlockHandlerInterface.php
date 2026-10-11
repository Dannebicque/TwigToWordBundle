<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Html\Handler;

use PhpOffice\PhpWord\Element\Section;

interface BlockHandlerInterface
{
    public function supports(\DOMElement $element): bool;

    /** @param array<string, mixed> $styles */
    public function render(\DOMElement $element, Section $section, array $styles): void;
}
