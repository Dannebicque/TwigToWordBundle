<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Controller;

use Davidannebicque\TwigToWordBundle\Word\WordRenderer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait WordTrait
{
    /** @param array<string, mixed> $context */
    protected function renderWord(string $template, array $context, string $filename, ?string $theme = null): BinaryFileResponse
    {
        return $this->container->get(WordRenderer::class)->renderFromTemplate($template, $context, $filename, $theme);
    }
}
