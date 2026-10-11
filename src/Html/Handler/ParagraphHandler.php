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
        // PHPWord TOC indexes Title elements, not ordinary TextRuns
        // with a Heading paragraph style.
        if (preg_match('/^h([1-6])$/', $tag, $matches) && ($style === 'Heading'.$matches[1])) {
            $section->addTitle(trim($element->textContent), (int) $matches[1]);
            return;
        }
        $run = $section->addTextRun($style !== '' ? $style : null);
        // PHPWord does not combine custom paragraph and font styles automatically.
        // Apply the named font as editable run formatting while retaining the paragraph style.
        $font = isset($styles[$style]['font']) && is_array($styles[$style]['font']) ? $styles[$style]['font'] : [];
        (new InlineTextRenderer())->render($element, $run, $font, $styles);
    }
}
