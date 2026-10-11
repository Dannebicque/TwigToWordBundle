# Architecture and scope

Twig is rendered to HTML, then parsed as a DOM, and finally converted into PHPWord objects.

The current HTML interpreter is a proof of concept. A future document AST and specialized handlers will separate HTML interpretation from DOCX generation.

## Themes

Configure named themes under `twig_to_word.themes`. A theme may extend another theme with the `extends` key. Recursive inheritance and cycle detection are provided by `ThemeRegistry`.

The initial renderer accepts paragraph style names through `data-docx-style`. Rich named Word styles, CSS parsing, section configuration, document-local themes, and inline style overrides are planned but not yet complete.

## Security

The parser uses `LIBXML_NONET`. Remote resources are not fetched by the current implementation. Do not introduce remote image fetching without explicit allowlists, timeouts and size limits.

## Roadmap

- Complete named Word paragraph/character/table styles
- Separate element handlers and document AST
- Expand safe image handling, section-specific headers/footers, and complex table merging
- Integration tests inspecting the generated DOCX XML

## PHPWord style lifecycle

PHPWord uses a static registry for named styles. The interpreter currently resets that registry before creating a new document. This avoids leaking named styles between *sequentially generated* documents, but it does **not** support holding multiple unsaved PhpWord instances with different themes in memory at the same time. Generate and save one document before starting another. Concurrent document generation in the same PHP process also requires additional isolation.

The long-term solution is a document-scoped style naming strategy or an isolated writer lifecycle, backed by DOCX XML integration tests. Do not claim simultaneous-document isolation until those tests exist.

## DOCX integration testing

`tests/DocxOutputTest.php` creates a real DOCX, opens the ZIP archive and inspects `word/styles.xml` and `word/document.xml`. This is a structural test, not a visual rendering comparison in Microsoft Word or LibreOffice.

## Current style limitation

PHPWord can register a font style with paragraph properties under a single name (`addFontStyle($name, $font, $paragraph)`). Its Word2007 writer serializes this combination as a native `w:style` of type `paragraph`, with both `w:pPr` and `w:rPr`. The paragraph handler now references that style without duplicating its font properties on each text run. As a result, changing the paragraph style in Word updates all unoverridden occurrences. Explicit inline markup still produces direct run formatting. `tests/DocxOutputTest.php` checks both the style definition and the absence of redundant run-level bold/color.

## Native character-style export compatibility

PHPWord 1.3 writes character-style definitions without `w:styleId` in some cases, although text runs reference that identifier through `w:rStyle`. `CharacterStyleDocxFixer` repairs the generated `word/styles.xml` after saving, adding the missing identifier for valid, named character styles. The fix runs through `WordRenderer::save()`; consumers who call `IOFactory::createWriter()` directly must run the fixer themselves. This is an OOXML compatibility workaround, not a replacement for PHPWord's writer. Tests inspect the repaired DOCX ZIP.
