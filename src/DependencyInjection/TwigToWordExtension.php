<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\DependencyInjection;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use Davidannebicque\TwigToWordBundle\Html\SafeImageResolver;
use Davidannebicque\TwigToWordBundle\Word\ThemeRegistry;
use Davidannebicque\TwigToWordBundle\Word\WordRenderer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

final class TwigToWordExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $themes = $config['themes'] ?? [];
        if (!isset($themes['default'])) {
            $themes['default'] = ['styles' => []];
        }
        $defaultTheme = $config['default_theme'];
        if (!isset($themes[$defaultTheme])) {
            throw new \InvalidArgumentException(sprintf('Default Word theme "%s" is not configured.', $defaultTheme));
        }
        $container->register(ThemeRegistry::class, ThemeRegistry::class)->setArgument(0, $themes);
        $container->register(SafeImageResolver::class, SafeImageResolver::class)
            ->setArgument(0, $config['images']['allowed_directories'])
            ->setArgument(1, $config['images']['max_bytes']);
        $container->register(HtmlDocumentInterpreter::class, HtmlDocumentInterpreter::class)
            ->setArgument(0, new Reference(SafeImageResolver::class));
        $container->register(WordRenderer::class, WordRenderer::class)
            ->setPublic(true)
            ->setArgument(0, new Reference('twig'))
            ->setArgument(1, new Reference(HtmlDocumentInterpreter::class))
            ->setArgument(2, new Reference(ThemeRegistry::class))
            ->setArgument(3, $defaultTheme);
    }
}
