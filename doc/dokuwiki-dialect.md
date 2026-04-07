# DokuWiki Markup Dialect Documentation

## Historical Context

### Origins: A Pragmatic File-Based Wiki

**DokuWiki** is a lightweight, file-based wiki engine created in **2004** by **Andreas Gohr** (known online as *andi*).
It was originally written as a personal solution for documentation needs at a time when many wiki systems required databases and were relatively heavy to deploy.

- **Project Name:** DokuWiki
- **Created:** 2004
- **Creator:** Andreas Gohr
- **Current Status:** Active open-source project
- **License:** GPL-2.0
- **Website:** https://www.dokuwiki.org

### Authors and Governance

- **Andreas Gohr** — creator, original author, and long-time lead maintainer
- Later development was supported by a **core team of contributors**, but unlike some wiki engines, DokuWiki never shifted to foundation-style governance; its direction remained relatively centralized and conservative.

### Design Philosophy

DokuWiki's syntax philosophy is **human-readable first, wiki-native second**:

1. **No database required** — all content stored as plain text files in a directory hierarchy
2. **Plain text readability** — a DokuWiki page should still be understandable when opened in a text editor
3. **Minimal punctuation noise** — syntax avoids excessive symbols compared to MediaWiki
4. **Consistency and predictability** — similar constructs behave similarly across contexts
5. **Extensibility without breaking core syntax** — advanced features are often implemented via plugins rather than syntax inflation

DokuWiki distinguished itself by storing all content as plain text files, making it particularly attractive for sysadmins, technical documentation teams, and environments with limited infrastructure or strict security requirements.

### Adoption

DokuWiki became especially popular where:
- Database use was discouraged or impossible
- Long-term readability and archival stability mattered
- Version control (e.g. backups, rsync, git) was preferred over dynamic storage

Prominent use cases:
- Enterprise internal documentation (especially in Europe)
- Software project documentation portals
- Network and system administration wikis
- Educational institutions and research groups
- Embedded documentation for hardware and offline systems

### Syntax Heritage

DokuWiki's syntax heritage sits between several traditions:

- **UseModWiki / early WikiWiki ideas** — emphasis on simplicity and plain text
- **MediaWiki** — some conceptual overlap (headings, links), but with less visual clutter
- **Lightweight markup languages** (pre-Markdown era) — similar goals, but DokuWiki evolved independently rather than adopting Markdown wholesale

Notably:
- DokuWiki **predates Markdown's dominance**, and did not originally align with it
- Markdown support was later added via plugins, but the native syntax remains primary
- Unlike reStructuredText or AsciiDoc, DokuWiki prioritizes **casual authoring** over publishing pipelines

---

## DokuWiki Core Syntax

The following elements are part of DokuWiki's native syntax.

### Bold
```
**bold text**
```
**Renders as:** **bold text**

### Italic (Emphasis)
```
//italic text//
```
**Renders as:** *italic text*

### Underline
```
__underlined text__
```
**Renders as:** <u>underlined text</u>

### Monospace (Teletype)
```
''monospace text''
```
Double single-quotes create monospace/typewriter text.

### Strikethrough
```
<del>deleted text</del>
```
Uses HTML-style `<del>` tags for strikethrough text.

### Superscript
```
<sup>superscript</sup>
```
Uses HTML-style `<sup>` tags.

### Subscript
```
<sub>subscript</sub>
```
Uses HTML-style `<sub>` tags.

### Headings
```
====== Heading 1 ======
===== Heading 2 =====
==== Heading 3 ====
=== Heading 4 ===
== Heading 5 ==
```
**Important:** DokuWiki headings are **inverted** compared to most wiki syntaxes — more `=` characters means a **higher-level** heading. `======` is H1 (largest), `==` is H5 (smallest). Closing `=` markers are required and must match the opening count.

### Links
```
[[http://example.com]]           — external link (auto-detected by scheme)
[[http://example.com|Click here]] — described external link
[[PageName]]                     — wiki page link
[[PageName|display text]]        — described wiki page link
[[PageName#section|anchor link]] — link with anchor
```
Links use double square brackets `[[ ]]` with pipe `|` separator for display text. Target starting with a URL scheme (`http://`, `https://`, etc.) creates an external link; otherwise creates a wiki page link.

### Interwiki Links
```
[[wiki>page]]              — interwiki link
[[wiki>page|display text]] — described interwiki link
```
DokuWiki uses `>` as the interwiki separator (e.g., `[[wp>DokuWiki]]` links to Wikipedia). This is recognized by the legacy parser but not modeled in the typed AST.

### Images
```
{{image.png}}                    — bare image
{{image.png|alt text}}           — image with alt text
{{ image.png}}                   — right-aligned (leading space)
{{image.png }}                   — left-aligned (trailing space)
{{ image.png }}                  — centered (both spaces)
{{image.png?200x100}}            — with dimensions
```
Images use double curly braces `{{ }}` with pipe `|` for alt text. Spacing around the source controls alignment. Dimensions can be appended with `?widthxheight`.

### Unordered Lists
```
  * Item 1
  * Item 2
    * Nested item
      * Deeply nested
```
Lists are **indentation-based**: 2 spaces per nesting level. Bullet items use `*` marker.

### Ordered Lists
```
  - First
  - Second
    - Nested numbered
```
Numbered items use `-` marker (not `#` like in Creole or MediaWiki).

### Tables
```
^ Header 1 ^ Header 2 ^
| Cell 1   | Cell 2   |
| Cell 3   | Cell 4   |
```
DokuWiki tables use two different delimiters: `^` for header cells and `|` for data cells. This is unique among wiki syntaxes — most others use a single delimiter with a prefix for headers.

Cell alignment is controlled by spacing:
- `|  right-aligned  |` — two spaces on both sides for center
- `|  right-aligned|` — leading spaces for right-align
- Default is left-aligned

### Code Block
```
<code>
preformatted text
no **markup** here
</code>
```
HTML-style `<code>` tags on their own lines create a preformatted block. An optional language can be specified: `<code php>`.

### Nowiki (Unformatted)
```
<nowiki>
**not bold** //not italic//
</nowiki>
```
Block-level nowiki uses `<nowiki>` tags.

### Nowiki — Inline
```
This is %%**not bold**%% text.
```
Double percent signs `%%` create inline unformatted text.

### Line Break
```
This is some text\\ with a forced newline
```
Double backslash `\\` forces a line break (must have whitespace before it in standard DokuWiki).

### Horizontal Rule
```
----
```
Four or more dashes on their own line.

### Blockquote
```
> quoted text
> more quoted text
>> nested quote
```
Greater-than `>` prefix for blockquote. Multiple `>` characters for nesting depth.

### Center
```
::centered text::
```
Double colons `::` around text for centering (between newlines in original DokuWiki).

### Definition List
```
; Term ; Definition
; Another term ; Another definition
```
Uses semicolons `;` as delimiters between term and definition.

### Footnotes
```
This has a footnote((footnote text))
```
Double parentheses create footnotes. **Not implemented in the modern AST.**

---

## Horde Implementation Details

### Key Syntax Differences from Other Dialects

| Feature | DokuWiki | Creole | MediaWiki |
|---|---|---|---|
| Heading direction | `======` = H1 (inverted) | `=` = H1 | `=` = H1 |
| List nesting | 2-space indentation | Character repetition | `:` + `*`/`#` |
| Numbered list marker | `-` | `#` | `#` |
| Header cells | `^` delimiter | `\|=` prefix | `!` prefix |
| Monospace | `''text''` | `{{{text}}}` | `<code>text</code>` |
| Code block | `<code>...</code>` | `{{{ }}}` | `<syntaxhighlight>` |
| Superscript | `<sup>text</sup>` | `^^text^^` | `<sup>text</sup>` |
| Subscript | `<sub>text</sub>` | `,,text,,` | `<sub>text</sub>` |
| Strikethrough | `<del>text</del>` | N/A | `<s>text</s>` |
| Nowiki inline | `%%text%%` | `{{{text}}}` | `<nowiki>text</nowiki>` |
| Center | `::text::` | `! text` | `<center>text</center>` |
| Deflist separator | `;` term `;` def | `;` term `:` def | `;` term `:` def |

---

## Gaps with DokuWiki Specification

### Implemented in Horde

All core DokuWiki elements commonly used in practice are supported:

| DokuWiki Element | Horde Status |
|---|---|
| Bold `**...**` | Supported |
| Italic `//...//` | Supported |
| Underline `__...__` | Supported |
| Monospace `''...''` | Supported |
| Strikethrough `<del>...</del>` | Supported |
| Superscript `<sup>...</sup>` | Supported |
| Subscript `<sub>...</sub>` | Supported |
| Headings `== ... ======` | Supported (inverted level mapping) |
| Links `[[...]]` | Supported (URL and wikilink disambiguation) |
| Images `{{...}}` | Supported (src and alt) |
| Unordered lists `  *` | Supported (indentation-based nesting) |
| Ordered lists `  -` | Supported (indentation-based nesting) |
| Tables `\|...\|` and `^...^` | Supported (header/data cell distinction) |
| Code block `<code>...</code>` | Supported (with optional language attribute) |
| Nowiki block `<nowiki>...</nowiki>` | Supported |
| Nowiki inline `%%...%%` | Supported |
| Line break `\\` | Supported |
| Horizontal rule `----` | Supported |
| Blockquote `>` | Supported |
| Center `::...::`| Supported |
| Definition list `; term ; def` | Supported |

### Not Implemented / Deferred

| Feature | Reason |
|---|---|
| Footnotes `((text))` | Niche feature, not modeled in typed AST |
| Interwiki links `[[wiki>page]]` | Recognized by legacy parser, not in typed AST |
| Image dimensions `{{img?200x100}}` | Image attributes beyond src/alt deferred |
| Image alignment (space-based) | Alignment via spacing deferred |
| Color text `~~color:text~~` | Legacy extension, niche use case |
| Revisions `@@---deleted+++inserted@@` | Legacy extension, niche use case |
| PHP lookup `[[php func_name]]` | Legacy extension, niche use case |
| Include `[[include file]]` | Legacy extension, niche use case |
| Embed `[[embed file]]` | Legacy extension, niche use case |
| Anchor `[[# name]]` | Legacy extension, niche use case |
| Table of Contents `{{TOC}}` | Legacy extension, niche use case |
| HTML blocks `<html>...</html>` | Security concern, intentionally excluded |
| Function documentation | Legacy extension, niche use case |

### Behavioral Differences from Standard DokuWiki

1. **List markers**: Standard DokuWiki uses `*` for unordered and `-` for ordered (with 2-space indentation). The Horde implementation correctly preserves this, unlike some implementations that confuse `-` with `#`.

2. **Heading level mapping**: DokuWiki inverts heading levels — `======` (6 equals) = H1, `=====` (5 equals) = H2, etc. The formula is `level = 7 - count_of_equals`. The Horde implementation correctly implements this inversion.

3. **Table cell delimiters**: DokuWiki uses `^` for header cells and `|` for data cells, which is unique among wiki syntaxes. Most others use a single delimiter with a prefix marker. The Horde implementation correctly distinguishes between `^` and `|` delimiters.

4. **Monospace syntax**: DokuWiki uses double single-quotes `''text''` (not backticks or `{{{ }}}`). This is the same syntax that MediaWiki uses for italic, which can cause confusion in cross-dialect scenarios.

5. **Strikethrough**: DokuWiki uses HTML-style `<del>text</del>` tags. The legacy parser also supported `@@---deleted+++inserted@@` revision markup, which is not implemented in the modern AST.

6. **Break syntax**: DokuWiki uses `\\` (double backslash) for line breaks, same as Creole. In standard DokuWiki, the break must be preceded by whitespace, but the Horde implementation matches `\\` without the whitespace requirement.

7. **Nowiki inline**: DokuWiki uses `%%text%%` for inline unformatted text. The legacy parser also supported `<nowiki>` blocks. Both forms are handled.

8. **Definition list format**: The legacy Horde parser uses `; term ; definition` (two semicolons), while standard DokuWiki documentation typically shows `; term` on one line and `: definition` on the next. The Horde implementation follows the legacy format for backward compatibility.

---

## Syntax Quick Reference

| Element | Syntax |
|---|---|
| Bold | `**text**` |
| Italic | `//text//` |
| Underline | `__text__` |
| Monospace | `''text''` |
| Strikethrough | `<del>text</del>` |
| Superscript | `<sup>text</sup>` |
| Subscript | `<sub>text</sub>` |
| Heading 1 (largest) | `====== text ======` |
| Heading 5 (smallest) | `== text ==` |
| Link | `[[target\|text]]` |
| Image | `{{src\|alt}}` |
| Bullet list | `  *` (2-space indent per level) |
| Numbered list | `  -` (2-space indent per level) |
| Table data cell | `\| content \|` |
| Table header cell | `^ content ^` |
| Code block | `<code>` ... `</code>` |
| Nowiki block | `<nowiki>` ... `</nowiki>` |
| Nowiki inline | `%%text%%` |
| Break | `\\` |
| Horiz rule | `----` |
| Blockquote | `> text` |
| Center | `::text::` |
| Deflist | `; term ; def` |

---

## References

- DokuWiki Official Website: https://www.dokuwiki.org
- DokuWiki Syntax Reference: https://www.dokuwiki.org/wiki:syntax
- Wikipedia: https://en.wikipedia.org/wiki/DokuWiki
- DokuWiki Source Code: https://github.com/dokuwiki/dokuwiki
