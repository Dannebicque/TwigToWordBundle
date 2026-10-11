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
- Add safe image handling, headers/footers, sections, table merging
- Integration tests inspecting the generated DOCX XML
