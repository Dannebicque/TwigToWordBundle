<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Word;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Twig\Environment;

final class WordRenderer
{
    public function __construct(
        private readonly Environment $twig,
        private readonly HtmlDocumentInterpreter $interpreter,
        private readonly ThemeRegistry $themes,
    ) {}

    /** @param array<string, mixed> $context */
    public function createFromTemplate(string $template, array $context = [], string $theme = 'default'): PhpWord
    {
        return $this->createFromHtml($this->twig->render($template, $context), $theme);
    }

    public function createFromHtml(string $html, string $theme = 'default'): PhpWord
    {
        return $this->interpreter->fromHtml($html, $this->themes->resolve($theme));
    }

    public function save(PhpWord $document, string $path): void
    {
        IOFactory::createWriter($document, 'Word2007')->save($path);
    }

    /** @param array<string, mixed> $context */
    public function renderFromTemplate(string $template, array $context, string $filename, string $theme = 'default'): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'twig_word_');
        if ($path === false) { throw new \RuntimeException('Cannot create temporary DOCX file.'); }
        try {
            $this->save($this->createFromTemplate($template, $context, $theme), $path);
        } catch (\Throwable $exception) {
            @unlink($path);
            throw $exception;
        }
        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);
        return $response;
    }
}
