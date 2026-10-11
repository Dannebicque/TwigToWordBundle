<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Html;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\TextRun;

final class HtmlDocumentInterpreter
{
    /** @param array<string, mixed> $theme */
    public function fromHtml(string $html, array $theme = []): PhpWord
    {
        $document = new PhpWord();
        $styles = $theme['styles'] ?? [];
        foreach ($styles as $name => $definition) {
            if (!is_array($definition)) { continue; }
            $font = $definition['font'] ?? [];
            $fontStyle = [
                'bold' => $font['bold'] ?? false,
                'size' => $font['size'] ?? 11,
                'color' => ltrim((string) ($font['color'] ?? '000000'), '#'),
            ];
            $paragraph = $definition['paragraph'] ?? [];
            if (!is_array($paragraph)) { $paragraph = []; }
            $document->addParagraphStyle((string) $name, $paragraph);
            $document->addFontStyle((string) $name.'_font', $fontStyle);
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8"><div id="twig-to-word-root">'.$html.'</div>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $sectionOptions = $theme['document'] ?? [];
        $section = $document->addSection(is_array($sectionOptions) ? array_intersect_key($sectionOptions, array_flip(['orientation', 'marginTop', 'marginBottom', 'marginLeft', 'marginRight'])) : []);
        $root = $dom->getElementById('twig-to-word-root');
        if ($root !== null) {
            $this->renderChildren($root, $section, $styles);
        }
        return $document;
    }

    /** @param array<string, mixed> $styles */
    private function renderChildren(\DOMNode $node, Section $section, array $styles): void
    {
        foreach ($node->childNodes as $child) {
            if (!$child instanceof \DOMElement) { continue; }
            $tag = strtolower($child->tagName);
            if (in_array($tag, ['script', 'style'], true)) { continue; }
            if ($tag === 'div' && $child->hasAttribute('data-docx-page-break')) {
                $section->addPageBreak();
                continue;
            }
            if (in_array($tag, ['h1','h2','h3','h4','h5','h6','p'], true)) {
                $style = $child->getAttribute('data-docx-style');
                $run = $section->addTextRun($style !== '' ? $style : null);
                $baseFont = ($style !== '' && isset($styles[$style]['font']) && is_array($styles[$style]['font'])) ? $this->normalizeFont($styles[$style]['font']) : [];
                $this->renderInline($child, $run, $baseFont, $styles);
            } elseif ($tag === 'table') {
                $table = $section->addTable();
                foreach ($child->getElementsByTagName('tr') as $tr) {
                    $table->addRow();
                    foreach ($tr->childNodes as $cell) {
                        if (!$cell instanceof \DOMElement || !in_array(strtolower($cell->tagName), ['td','th'], true)) { continue; }
                        $table->addCell()->addText(trim($cell->textContent));
                    }
                }
            } elseif ($tag === 'ul' || $tag === 'ol') {
                foreach ($child->childNodes as $li) {
                    if ($li instanceof \DOMElement && strtolower($li->tagName) === 'li') {
                        $section->addListItem(trim($li->textContent), 0, null, $tag === 'ol' ? 'decimal' : 'bullet');
                    }
                }
            } else {
                $this->renderChildren($child, $section, $styles);
            }
        }
    }

    /** @param array<string, mixed> $font @return array<string, mixed> */
    private function normalizeFont(array $font): array
    {
        $result = [];
        foreach (['bold', 'italic', 'size', 'name', 'underline'] as $key) {
            if (array_key_exists($key, $font)) { $result[$key] = $font[$key]; }
        }
        if (isset($font['color'])) { $result['color'] = ltrim((string) $font['color'], '#'); }
        return $result;
    }

    /** @param array<string, mixed> $style @param array<string, mixed> $styles */
    private function renderInline(\DOMNode $node, TextRun $run, array $style, array $styles): void
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMText) {
                if ($child->textContent !== '') { $run->addText($child->textContent, $style); }
                continue;
            }
            if (!$child instanceof \DOMElement) { continue; }
            $next = $style;
            $tag = strtolower($child->tagName);
            if (in_array($tag, ['strong','b'], true)) { $next['bold'] = true; }
            if (in_array($tag, ['em','i'], true)) { $next['italic'] = true; }
            if ($tag === 'u') { $next['underline'] = 'single'; }
            if ($tag === 'br') { $run->addTextBreak(); continue; }
            $named = $child->getAttribute('data-docx-style');
            if ($named !== '' && isset($styles[$named]['font']) && is_array($styles[$named]['font'])) {
                $font = $styles[$named]['font'];
                $next = array_merge($next, array_filter([
                    'bold' => $font['bold'] ?? null,
                    'italic' => $font['italic'] ?? null,
                    'size' => $font['size'] ?? null,
                    'color' => isset($font['color']) ? ltrim((string) $font['color'], '#') : null,
                ], static fn ($v) => $v !== null));
            }
            if ($child->hasAttribute('data-docx-font-color')) { $next['color'] = ltrim($child->getAttribute('data-docx-font-color'), '#'); }
            if ($child->hasAttribute('data-docx-font-bold')) { $next['bold'] = $child->getAttribute('data-docx-font-bold') === 'true'; }
            $this->renderInline($child, $run, $next, $styles);
        }
    }
}
