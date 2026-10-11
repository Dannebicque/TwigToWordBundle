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

## Headers, footers and page numbers

Theme configuration supports optional `header` and `footer` entries:

```yaml
twig_to_word:
  themes:
    default:
      header:
        text: 'University report'
        alignment: right
      footer:
        text: 'Page'
        alignment: center
        page_numbers: true
```

The page number is a native Word `PAGE` field, not a fixed number. Header/footer text is plain text (not interpreted as HTML); alignment can be `left`, `center`, or `right`.

## Editable table of contents

Insert a native Word table of contents in a Twig template:

```html
<div data-docx-toc data-docx-toc-min="1" data-docx-toc-max="3"></div>
<h1>Introduction</h1>
<h2>Background</h2>
```

The DOCX contains a Word TOC field, not a static HTML listing. In Word, update the field/table of contents to refresh headings and page numbers after editing.

Footer options `page_numbers: true` and `total_pages: true` produce `PAGE` and `NUMPAGES` fields. Use `first_page: true` on a header or footer configuration to target the first-page variant (requires the document's first-page header/footer setting in Word). Table cells accept HTML `colspan` and `rowspan`.

## Word feature coverage

See [Word / PHPWord / TwigToWordBundle feature matrix](docs/word-feature-matrix.md) for supported mappings, limitations, examples, and the prioritized roadmap.

### Multiple sections and cover pages

Create a cover page and switch orientation for wide tables without converting the report to an image:

```html
<h1>Report cover</h1>
<div data-docx-page-break></div>
<div data-docx-toc data-docx-toc-min="1" data-docx-toc-max="3"></div>
<h1>Introduction</h1>
<div data-docx-section-break data-docx-orientation="landscape">
  <h2>Wide results</h2>
  <table><tr><th>Metric</th><th>Value</th></tr><tr><td>Users</td><td>100</td></tr></table>
</div>
<div data-docx-section-break data-docx-orientation="portrait">
  <h2>Appendix</h2>
</div>
```

Each `data-docx-section-break` creates a new Word section. Content after it continues in that section until another section break. Orientations are restricted to `portrait` and `landscape`. Configure `document.differentFirstPage: true` and `header.first_page: true` for a dedicated cover-page header. Word may require field updates to refresh the TOC and page counts.

### Section-specific margins, headers and footers

```html
<div data-docx-section-break
     data-docx-orientation="landscape"
     data-docx-margin-left="900"
     data-docx-margin-right="900"
     data-docx-header-text="Results"
     data-docx-footer-text="Confidential">
  <h2>Detailed results</h2>
</div>
```

Margin attributes (`data-docx-margin-top`, `-bottom`, `-left`, `-right`) accept integer twips from 0 to 20000. Header/footer text overrides apply to the new section and remain editable. They override only the text while preserving other theme settings (such as alignment, page numbering and total-page fields). An explicitly empty attribute clears the text. Nested section breaks inherit the current override. For a different first-page header, use the theme's `document.differentFirstPage` and `header.first_page` settings.

### Native editable Word styles

Custom theme styles containing `font` and `paragraph` properties are now exported as **one native Word paragraph style** rather than as separate, unrelated styles or direct formatting on every text run:

```yaml
twig_to_word:
  themes:
    default:
      styles:
        ReportTitle:
          paragraph:
            alignment: center
          font:
            name: Aptos Display
            size: 22
            bold: true
            color: '17365D'
```

Use `<h1 data-docx-style="ReportTitle">Report</h1>` or `<p data-docx-style="ReportTitle">Report</p>`. In Word, modify the **ReportTitle** style to update every paragraph using it. Explicit inline `<strong>`, `<em>` or `data-docx-font-*` formatting still overrides the style locally. Native `Heading1` through `Heading6` remain compatible with the table of contents.

### Reusable Word character styles

Define a named character style in the theme and reference it from a simple inline span:

```yaml
twig_to_word:
  themes:
    default:
      styles:
        Important:
          type: character
          font:
            bold: true
            color: C00000
```

```html
<p>Read the <span data-docx-style="Important">important</span> notice.</p>
```

The exported span references Word's native `Important` character style, so updating the style in Word updates its occurrences. For now this direct style reference applies to a simple text-only span without additional inline overrides; nested markup uses the existing inline-formatting fallback.

### CI concurrency

The GitHub Actions workflow uses a concurrency group per pull request or branch and `cancel-in-progress: true`. Pushing a newer commit cancels any superseded run for that PR or branch. The PHP 8.2/8.3/8.4 matrix uses `fail-fast: false` so a test failure on one PHP version does not cancel diagnostics for the others.
