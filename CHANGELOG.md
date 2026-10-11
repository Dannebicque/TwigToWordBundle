# Changelog

## 0.1.0 RC1 — 2026-10-11

First integration-testing release candidate (branch `rc/0.1.0`).

### Features
- Symfony Bundle integration with Twig templates and configurable themes.
- Editable DOCX output via PHPWord with native paragraph, character and table styles.
- Paragraph style inheritance (`basedOn`) and following style (`next`), including combined font/paragraph styles through a DOCX compatibility pass.
- Headings, native table of contents, page breaks and multiple sections.
- Tables with merged cells, headers, row groups and nested editable tables.
- Paragraphs, inline bold/italic/underline, links and ordered/unordered lists.
- Headers, footers, first-page variants and PAGE/NUMPAGES fields.
- Images restricted to configured local directories, with size/MIME checks and temporary snapshot cleanup.
- PHPUnit tests and GitHub Actions PHP 8.2/8.3/8.4 matrix; obsolete CI runs cancelled.

### Known limitations
- PHPWord keeps named styles in a process-global registry. Save each document before constructing the next document with a different theme.
- The HTML input is a supported subset, not a browser-complete HTML/CSS renderer.
- Native character-style compatibility corrections and combined-style `next` are applied by `WordRenderer::save()`, not direct PHPWord writers.
- TOC and page-number fields may require an update in Microsoft Word after opening.
- This is an RC branch, not a published SemVer tag or a Packagist stable release.
