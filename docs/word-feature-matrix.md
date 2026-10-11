# Microsoft Word vs PHPWord vs TwigToWordBundle

This matrix distinguishes what can be edited in Microsoft Word, what PHPWord offers as an API, and what **this bundle currently maps from Twig/HTML**. It is not a claim of complete DOCX compatibility.

Legend: **Yes** = implemented in the bundle; **Partial** = limited mapping or pending verification; **No** = no bundle mapping; **API** = PHPWord supports the concept, but the bundle does not expose it.

| Feature | Word | PHPWord | TwigToWordBundle | Twig / configuration |
|---|---|---|---|---|
| Paragraphs | Yes | Yes | Yes | `<p>` |
| Headings / outline | Yes | Yes | Yes | `<h1>`…`<h6>` (native Title) |
| Bold / italic / underline | Yes | Yes | Yes | `<strong>`, `<em>`, `<u>` |
| Named paragraph styles | Yes | Yes | Partial | `data-docx-style`; configured font is applied as editable direct run formatting |
| Inline font color / bold | Yes | Yes | Yes | `data-docx-font-color`, `data-docx-font-bold` |
| Page breaks | Yes | Yes | Yes | `<div data-docx-page-break>` |
| Bulleted / numbered lists | Yes | Yes | Yes | `<ul>`, `<ol>`, nested `<li>` |
| Complex list numbering / restarts | Yes | Yes | No | Not mapped |
| Tables | Yes | Yes | Yes | `<table>`, `<tr>`, `<td>`, `<th>` |
| Header cells / inline formatting | Yes | Yes | Yes | `<th>`, inline HTML |
| Merged cells | Yes | Yes | Partial | `colspan`, `rowspan`; complex layouts need tests |
| Cell width | Yes | Yes | Partial | `data-docx-width` in twips |
| Images / drawings | Yes | Yes | Partial | Local PNG/JPEG/GIF only, configured allowlist; no SVG/remote URLs |
| Hyperlinks | Yes | Yes | Yes | `<a href="https://…">`, HTTP(S) / mailto only |
| Header / footer | Yes | Yes | Partial | Theme `header`, `footer` plain text |
| First-page header/footer | Yes | Yes | Partial | `first_page`; document settings to verify |
| Page number / total pages | Yes | Yes | Yes | `page_numbers`, `total_pages` |
| Automatic table of contents | Yes | Yes | Partial | `data-docx-toc`; Word refresh may be required |
| Multiple sections | Yes | Yes | No | One section per document |
| Portrait / landscape / margins | Yes | Yes | Partial | Theme `document` |
| Footnotes / endnotes | Yes | Yes | No | Not mapped |
| Comments / tracked changes | Yes | Yes | No | Not mapped |
| Bookmarks / cross references | Yes | Yes | No | Not mapped |
| Charts / embedded objects | Yes | Yes | No | Not mapped |
| Content controls / forms | Yes | Partial | No | Not mapped |
| Page borders / watermarks | Yes | Partial | No | Not mapped |
| Document properties / metadata | Yes | Yes | No | Not mapped |
| Track changes in Word after generation | Yes | N/A | Yes | Output is an editable DOCX |

## Examples

### Automatic table of contents

```html
<div data-docx-toc data-docx-toc-min="1" data-docx-toc-max="3"></div>
<h1>Introduction</h1>
<h2>Background</h2>
```

The headings must be native PHPWord Title elements to be indexed. Open the document in Word and update the table of contents to refresh page numbers.

### Header and footer

```yaml
twig_to_word:
  themes:
    default:
      header:
        text: 'Institution'
        alignment: right
      footer:
        text: 'Page'
        page_numbers: true
        total_pages: true
        alignment: center
```

### Merged cells

```html
<table>
  <tr><th colspan="2">Overview</th></tr>
  <tr><td rowspan="2">Category</td><td>Value A</td></tr>
  <tr><td>Value B</td></tr>
</table>
```

## Architectural gaps and priorities

1. **High:** validate TOC, first-page header/footer, and merged cells by inspecting DOCX OOXML and opening in Word/LibreOffice.
2. **High:** improve native named-style font inheritance (currently direct run formatting) and isolate PHPWord's process-global named style registry.
3. **Medium:** add safe local images, bookmarks and cross-references.
4. **Medium:** richer tables (repeated header rows, widths, borders), numbering definitions and section breaks.
5. **Later:** footnotes, comments, document properties, page backgrounds and advanced Word layout.

## Compatibility notes

The bundle is a **selected HTML-to-DOCX mapping**, not a browser/CSS rendering engine. Unsupported HTML/CSS is not guaranteed to be preserved. The underlying PHPWord feature list is broader than the bundle's current handlers.

References: https://phpoffice.github.io/PHPWord/usage/elements.html and https://phpoffice.github.io/PHPWord/usage/containers.html.

## Hyperlinks

External links in `<a href="https://example.org">Label</a>` are emitted as native clickable Word hyperlinks. Only `http:`, `https:` and `mailto:` are accepted; unsafe or unsupported URLs are emitted as plain text. Images use a strict local-directory allowlist. Remote URLs, data URIs and SVG are rejected.

## Secure images

```yaml
twig_to_word:
  images:
    allowed_directories:
      - '%kernel.project_dir%/public/word-assets'
    max_bytes: 5242880
```

```html
<img src="logo.png" data-docx-width="160" alt="Institution logo">
```

Image resolution is relative to the configured directories. The default allowlist is empty: **images are disabled unless explicitly configured**. The resolver verifies the canonical path using `realpath` (including symlink escapes), rejects URL schemes and data URIs, checks file size, MIME type and image dimensions, and accepts only PNG, JPEG and GIF. No network access is performed. Unsupported images are skipped. For untrusted concurrently writable asset directories, avoid accepting user-controlled symlinks or file replacements between validation and DOCX writing.

## Image packaging verification

`tests/DocxImageTest.php` writes a real DOCX and checks that `word/media/` contains the embedded image, `word/_rels/document.xml.rels` declares an image relationship and `word/document.xml` references either DrawingML or VML image markup (both are generated by PHPWord depending on its writer). This is stronger than only checking the PHPWord object tree; it is not a visual Word/LibreOffice rendering test.

For security, absolute paths and `.` / `..` path segments are rejected, even if they would resolve within an allowlisted directory. The image handler now creates a bounded, revalidated private snapshot before passing the image to PHPWord, reducing exposure to source-file replacement after validation. The bundle's `WordRenderer::save()` deletes snapshots in a `finally` block. If you bypass `WordRenderer` and call `IOFactory::createWriter()` directly, call `HtmlDocumentInterpreter::cleanup($document)` after saving. The initial path opening still has a race window in a directory writable by untrusted processes: keep the allowlisted asset directory read-only during generation.

## Merged table OOXML test

`tests/MergedTableDocxTest.php` checks that an exported DOCX contains `w:gridSpan` and `w:vMerge` for HTML `colspan` / `rowspan`. This verifies serialization, but complex mixed row/column merges and visual interoperability still require further test cases.

## Regression coverage

- `tests/ImageLifecycleTest.php`: snapshot isolation, private file permissions and idempotent cleanup.
- `tests/DocxOutputTest.php`: verifies custom paragraph styles and direct run font formatting in `word/document.xml`.
- `tests/DocxImageTest.php`: verifies embedded image media and document relationships.
- `tests/MergedTableDocxTest.php`: verifies merged-cell OOXML.

**Important:** A DOCX containing embedded image snapshots must be saved before calling `HtmlDocumentInterpreter::cleanup()`. The bundle's `WordRenderer::save()` handles this automatically. Applications calling PHPWord's writer directly are responsible for cleanup.

## Complex table merges and first-page fields

Rowspan continuation is now emitted only on subsequent rows. Combined `rowspan` and `colspan` retain the `w:gridSpan` value on continuation cells; `tests/MergedTableDocxTest.php` checks each XML row separately to catch misplaced continuations. `tests/HeaderFooterTest.php` also checks `PAGE`, `NUMPAGES`, and first-page header references in exported DOCX files.

## First-page headers

For a first-page-specific header, configure the section's `differentFirstPage` option as well as `header.first_page`:

```yaml
twig_to_word:
  themes:
    default:
      document:
        differentFirstPage: true
      header:
        text: 'Cover page'
        first_page: true
```

The exported `word/document.xml` should include `w:titlePg` and a header reference of type `first`. A first-page-only header is distinct from a regular header on later pages.

## Error handling

The interpreter now cleans its tracked temporary image snapshots if HTML conversion throws an exception, including invalid TOC ranges. `tests/RenderingFailureCleanupTest.php` checks this failure path. A successful direct PHPWord export still requires explicit `cleanup()` if `WordRenderer::save()` is not used.
