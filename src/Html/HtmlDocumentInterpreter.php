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
    /** @var array<int, list<string>> */
    private array $temporaryImages = [];
    private ?int $currentDocumentId = null;

    public function __construct(private readonly ?SafeImageResolver $images = null) {}

    public function cleanup(PhpWord $document): void
    {
        $id = spl_object_id($document);
        foreach ($this->temporaryImages[$id] ?? [] as $file) { @unlink($file); }
        unset($this->temporaryImages[$id]);
    }

    /** @param array<string, mixed> $theme */
    public function fromHtml(string $html, array $theme = []): PhpWord
    {
        $registrar = new WordStyleRegistrar();
        $registrar->reset();
        $document = new PhpWord();
        $this->currentDocumentId = spl_object_id($document);
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
        $section = $document->addSection(is_array($sectionOptions) ? array_intersect_key($sectionOptions, array_flip(['orientation', 'marginTop', 'marginBottom', 'marginLeft', 'marginRight', 'differentFirstPage'])) : []);
        (new HeaderFooterRenderer())->apply($section, $theme);
        // HTML parsing does not declare the id attribute as an XML ID.
        // Locate our wrapper deterministically instead of relying on getElementById().
        $root = (new \DOMXPath($dom))->query('//*[@id="twig-to-word-root"]')->item(0);
        try {
            if ($root !== null) {
                $this->renderChildren($root, $section, $styles, $document, $theme, is_array($sectionOptions) ? $sectionOptions : []);
            }
            return $document;
        } catch (\Throwable $exception) {
            $this->cleanup($document);
            throw $exception;
        } finally {
            $this->currentDocumentId = null;
        }
    }

    /** @param array<string, mixed> $styles */
    private function renderChildren(\DOMNode $node, Section $section, array $styles, PhpWord $document, array $theme, array $sectionOptions): Section
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
            if ($child->hasAttribute('data-docx-section-break')) {
                $options = $sectionOptions;
                $orientation = $child->getAttribute('data-docx-orientation');
                if ($orientation !== '') {
                    if (!in_array($orientation, ['portrait', 'landscape'], true)) {
                        throw new \InvalidArgumentException('Invalid Word section orientation.');
                    }
                    $options['orientation'] = $orientation;
                }
                foreach (['marginTop', 'marginBottom', 'marginLeft', 'marginRight'] as $margin) {
                    $attribute = 'data-docx-'.strtolower(preg_replace('/[A-Z]/', '-$0', $margin));
                    $value = $child->getAttribute($attribute);
                    if ($value !== '') {
                        if (!ctype_digit($value) || (int) $value > 20000) {
                            throw new \InvalidArgumentException('Invalid Word section margin: '.$margin);
                        }
                        $options[$margin] = (int) $value;
                    }
                }
                $section = $document->addSection(array_intersect_key($options, array_flip(['orientation', 'marginTop', 'marginBottom', 'marginLeft', 'marginRight', 'differentFirstPage'])));
                (new HeaderFooterRenderer())->apply($section, $theme);
                $section = $this->renderChildren($child, $section, $styles, $document, $theme, $options);
                continue;
            }
            if ($tag === 'div' && $child->hasAttribute('data-docx-page-break')) {
                $section->addPageBreak();
                continue;
            }
            $handlers = [new ParagraphHandler(), new TableHandler(), new ListHandler(), new ImageHandler($this->images ?? new SafeImageResolver(), function (string $file): void {
                if ($this->currentDocumentId !== null) {
                    $this->temporaryImages[$this->currentDocumentId][] = $file;
                }
            })];
            $handled = false;
            foreach ($handlers as $handler) {
                if ($handler->supports($child)) {
                    $handler->render($child, $section, $styles);
                    $handled = true;
                    break;
                }
            }
            if (!$handled) {
                $section = $this->renderChildren($child, $section, $styles, $document, $theme, $sectionOptions);
            }
        }
        return $section;
    }

}
