<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Html;

use PhpOffice\PhpWord\PhpWord;
use Davidannebicque\TwigToWordBundle\Word\WordStyleRegistrar;
use Davidannebicque\TwigToWordBundle\Word\HeaderFooterRenderer;
use Davidannebicque\TwigToWordBundle\Html\Handler\TableHandler;
use Davidannebicque\TwigToWordBundle\Html\Handler\ParagraphHandler;
use Davidannebicque\TwigToWordBundle\Html\Handler\ListHandler;
use Davidannebicque\TwigToWordBundle\Html\Handler\ImageHandler;
use PhpOffice\PhpWord\Element\Section;

final class HtmlDocumentInterpreter
{
    public function __construct(private readonly ?SafeImageResolver $images = null) {}

    /** @param array<string, mixed> $theme */
    public function fromHtml(string $html, array $theme = []): PhpWord
    {
        $registrar = new WordStyleRegistrar();
        $registrar->reset();
        $document = new PhpWord();
        $styles = $theme['styles'] ?? [];
        $registrar->register($document, is_array($styles) ? $styles : []);
        for ($level = 1; $level <= 6; ++$level) {
            if (!isset($styles['Heading'.$level])) {
                $document->addTitleStyle($level, ['bold' => true, 'size' => max(11, 20 - $level * 2)]);
            }
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
        (new HeaderFooterRenderer())->apply($section, $theme);
        // HTML parsing does not declare the id attribute as an XML ID.
        // Locate our wrapper deterministically instead of relying on getElementById().
        $root = (new \DOMXPath($dom))->query('//*[@id="twig-to-word-root"]')->item(0);
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
            if ($child->hasAttribute('data-docx-toc')) {
                $min = (int) ($child->getAttribute('data-docx-toc-min') ?: 1);
                $max = (int) ($child->getAttribute('data-docx-toc-max') ?: 3);
                if ($min < 1 || $max > 9 || $min > $max) {
                    throw new \InvalidArgumentException('Invalid table of contents heading range.');
                }
                $section->addTOC(null, null, $min, $max);
                continue;
            }
            if ($tag === 'div' && $child->hasAttribute('data-docx-page-break')) {
                $section->addPageBreak();
                continue;
            }
            $handlers = [new ParagraphHandler(), new TableHandler(), new ListHandler(), new ImageHandler($this->images ?? new SafeImageResolver())];
            $handled = false;
            foreach ($handlers as $handler) {
                if ($handler->supports($child)) {
                    $handler->render($child, $section, $styles);
                    $handled = true;
                    break;
                }
            }
            if (!$handled) {
                $this->renderChildren($child, $section, $styles);
            }
        }
    }

}
