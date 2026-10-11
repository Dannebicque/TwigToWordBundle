<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Html\Handler;
use PhpOffice\PhpWord\Element\TextRun;
final class InlineTextRenderer
{
    public function render(\DOMNode $node, TextRun $run, array $font = [], array $styles = []): void
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMText) {
                if ($child->textContent !== '') {
                    $run->addText($child->textContent, $this->normalize($font));
                }
                continue;
            }
            if (!$child instanceof \DOMElement) { continue; }
            $tag = strtolower($child->tagName);
            if ($tag === 'br') { $run->addTextBreak(); continue; }
            if ($tag === 'a') {
                $href = trim($child->getAttribute('href'));
                if ($this->isSafeLink($href)) {
                    $run->addLink($href, trim($child->textContent), ['color' => '0563C1', 'underline' => 'single']);
                } else {
                    $this->render($child, $run, $font, $styles);
                }
                continue;
            }
            if (in_array($tag, ['script', 'style'], true)) { continue; }
            $next = $font;
            if (in_array($tag, ['strong', 'b'], true)) { $next['bold'] = true; }
            if (in_array($tag, ['em', 'i'], true)) { $next['italic'] = true; }
            if ($tag === 'u') { $next['underline'] = 'single'; }
            $named = $child->getAttribute('data-docx-style');
            if ($named !== '' && isset($styles[$named]['font']) && is_array($styles[$named]['font'])) {
                $next = array_replace($next, $styles[$named]['font']);
            }
            if ($child->hasAttribute('data-docx-font-color')) {
                $next['color'] = $child->getAttribute('data-docx-font-color');
            }
            if ($child->hasAttribute('data-docx-font-bold')) {
                $next['bold'] = $child->getAttribute('data-docx-font-bold') === 'true';
            }
            $this->render($child, $run, $next, $styles);
        }
    }
    private function isSafeLink(string $href): bool
    {
        if (preg_match('/[\\x00-\\x1F\\x7F]/', $href)) { return false; }
        $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));
        return in_array($scheme, ['https', 'http', 'mailto'], true);
    }

    private function normalize(array $font): array
    {
        $result = array_intersect_key($font, array_flip(['name', 'size', 'color', 'bold', 'italic', 'underline']));
        if (isset($result['color'])) { $result['color'] = ltrim((string) $result['color'], '#'); }
        return $result;
    }
}
