# TwigToWordBundle

A Symfony bundle for generating editable Word (DOCX) documents from Twig templates.

> **Experimental prototype:** the HTML interpreter currently handles simple paragraphs, headings, inline bold/italic/underline, tables, lists, and page breaks. Not production-ready.

## Requirements

PHP 8.2+, Symfony 6.4/7/8, PHPWord 1.3+.

## Installation (development)

Until a tagged release is published, use a Composer VCS repository pointing to this GitHub repository and require the development branch.

## Usage

Inject `Davidannebicque\\TwigToWordBundle\\Word\\WordRenderer`:

```php
$document = $renderer->createFromTemplate(
    'documents/report.html.twig',
    ['title' => 'Annual report'],
    'institutionnel'
);
$renderer->save($document, '/tmp/report.docx');

// Or return a download response:
return $renderer->renderFromTemplate(
    'documents/report.html.twig',
    ['title' => 'Annual report'],
    'report.docx',
    'institutionnel'
);
```

## Theme configuration

```yaml
# config/packages/twig_to_word.yaml
twig_to_word:
    themes:
        base:
            styles:
                title:
                    font:
                        size: 22
                        bold: true
                        color: '17365D'
        institutionnel:
            extends: base
            styles:
                title:
                    font:
                        size: 26
```

```twig
<h1 data-docx-style="title">{{ title }}</h1>
<p>Introduction <strong>important</strong>.</p>
```

Theme inheritance is supported. The current implementation applies theme paragraph styles and font properties to paragraph content. Document-local theme declarations and complete Word style semantics are still planned.

## Architecture

Twig -> HTML DOM -> PHPWord. A dedicated intermediate AST and per-element handlers are planned for the next development phase.

See [architecture notes](docs/architecture.md).

## Tests

```bash
composer install
vendor/bin/phpunit tests
```

## License

MIT (license file to be added before release).

## Important: style registry lifecycle

PHPWord stores named styles in a process-wide registry. TwigToWordBundle currently resets it at the beginning of each conversion. When generating multiple documents with different themes, **save each DOCX before creating the next**. Keeping several unfinished documents in memory is not supported yet. See [architecture notes](docs/architecture.md).
