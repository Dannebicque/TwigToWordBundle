# Microsoft Word vs PHPWord vs TwigToWordBundle

This matrix distinguishes what can be edited in Microsoft Word, what PHPWord offers as an API, and what **this bundle currently maps from Twig/HTML**. It is not a claim of complete DOCX compatibility.

Legend: **Yes** = implemented in the bundle; **Partial** = limited mapping or pending verification; **No** = no bundle mapping; **API** = PHPWord supports the concept, but the bundle does not expose it.

| Feature | Word | PHPWord | TwigToWordBundle | Twig / configuration |
|---|---|---|---|---|
| Paragraphs | Yes | Yes | Yes | `<p>` |
| Headings / outline | Yes | Yes | Yes | `<h1>`…`<h6>` (native Title) |
| Bold / italic / underline | Yes | Yes | Yes | `<strong>`, `<em>`, `<u>` |
| Named paragraph styles | Yes | Yes | Partial | `data-docx-style`; font inheritance needs work |
| Inline font color / bold | Yes | Yes | Yes | `data-docx-font-color`, `data-docx-font-bold` |
| Page breaks | Yes | Yes | Yes | `<div data-docx-page-break>` |
| Bulleted / numbered lists | Yes | Yes | Yes | `<ul>`, `<ol>`, nested `<li>` |
| Complex list numbering / restarts | Yes | Yes | No | Not mapped |
| Tables | Yes | Yes | Yes | `<table>`, `<tr>`, `<td>`, `<th>` |
| Header cells / inline formatting | Yes | Yes | Yes | `<th>`, inline HTML |
| Merged cells | Yes | Yes | Partial | `colspan`, `rowspan`; complex layouts need tests |
| Cell width | Yes | Yes | Partial | `data-docx-width` in twips |
| Images / drawings | Yes | Yes | No | Not mapped |
| Hyperlinks | Yes | Yes | No | `<a>` not mapped |
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
2. **High:** fix paragraph style font inheritance and isolate PHPWord's process-global named style registry.
3. **Medium:** add safe local images, links, bookmarks and cross-references.
4. **Medium:** richer tables (repeated header rows, widths, borders), numbering definitions and section breaks.
5. **Later:** footnotes, comments, document properties, page backgrounds and advanced Word layout.

## Compatibility notes

The bundle is a **selected HTML-to-DOCX mapping**, not a browser/CSS rendering engine. Unsupported HTML/CSS is not guaranteed to be preserved. The underlying PHPWord feature list is broader than the bundle's current handlers.

References: https://phpoffice.github.io/PHPWord/usage/elements.html and https://phpoffice.github.io/PHPWord/usage/containers.html.
