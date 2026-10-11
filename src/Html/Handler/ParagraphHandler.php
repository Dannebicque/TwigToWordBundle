<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Html\Handler;
use PhpOffice\PhpWord\Element\Section;
final class ParagraphHandler implements BlockHandlerInterface
{
    public function supports(\DOMElement $element): bool
    {
        return in_array(strtolower($element->tagName), ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true);
    }
    public function render(\DOMElement $element, Section $section, array $styles): void
    {
        $style = $element->getAttribute('data-docx-style');
        $tag = strtolower($element->tagName);
        if ($style === '' && strlen($tag) === 2 && $tag[0] === 'h') {
            $style = 'Heading'.substr($tag, 1);
        }
        $run = $section->addTextRun($style !== '' ? $style : null);
        // The paragraph's named Word style supplies the base font.
        // Only inline markup and explicit local overrides create run formatting.
        (new InlineTextRenderer())->render($element, $run, [], $styles);
    }
}
