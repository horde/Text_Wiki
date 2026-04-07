# Creole Wiki Markup Dialect Documentation

## Historical Context

### Origins: A Markup Standard, Not a Wiki Engine

**Creole** (often called **WikiCreole** or **Creole 1.0**) is unique among the dialects supported by Horde Text_Wiki: it is **not a wiki engine**, but a **wiki markup standard**. It emerged in **2006–2007** as an explicit attempt to solve the "wiki markup mess" — the proliferation of mutually incompatible wiki syntaxes across dozens of engines.

- **Project Name:** WikiCreole / Creole 1.0
- **Conceived:** 2006 (WikiSym workshop)
- **Version 1.0 Released:** July 4, 2007
- **Current Status:** Stable specification (deliberately frozen after 1.0)
- **License:** Public specification
- **Website:** https://www.wikicreole.org

### Authors and Governance

Creole was a **collaborative design effort**, not a single-author project.

Core figures:
- **Christoph Sauer** — primary specification author
- **Chuck Smith** — specification co-author
- **Tomas Benz** — specification co-author
- **Ward Cunningham** — inventor of the wiki, coined the name "Creole" (drawing an analogy to natural creole languages as stable mixtures derived from multiple parents)

Governance was consensus-driven, involving many wiki engine developers and users via discussion pages, polls, and iterative drafts at the International Symposium on Wikis (WikiSym).

### Design Philosophy

Creole's syntax philosophy is **interoperability before expressiveness**:

1. **Subset, not superset** — only constructs widely shared across existing engines were included
2. **Low ambiguity** — syntax choices evaluated for ease of parsing and consistent interpretation
3. **Human-writable plain text** — editing remains comfortable in a simple textarea
4. **Second language model** — Creole explicitly does *not* replace native markup; it coexists with it
5. **Conservative and minimal** — advanced or engine-specific features intentionally excluded

The specification was formalized through an **EBNF grammar** and **XML interchange format**, unusual for wiki markup at the time.

### Adoption

Engines and platforms that implemented Creole (natively or via plugins):
- DokuWiki, MoinMoin, PmWiki, Oddmuse, TiddlyWiki, JSPWiki, Trac (partial)
- Bitbucket Wikis (default syntax with extensions)
- Moodle (educational platform)
- PlantUML (diagram annotations)

Creole saw **broad but shallow adoption**: many implementations, few exclusive deployments. It ultimately lost the "lightweight markup" race to Markdown, which had simpler syntax and broader tooling.

### Conceptual Lineage

- Comparative analysis of **MediaWiki, MoinMoin, PmWiki, TWiki**, and dozens of others
- When no common syntax existed, **MediaWiki's form was often chosen** due to prevalence
- Named after **natural creole languages** — stable languages formed from multiple parents
- Occupies a space between early WikiWiki syntax, platform-specific syntaxes (MediaWiki), and later efforts (Markdown, reStructuredText)

---

## Creole 1.0 Core Specification

The following elements are defined in the Creole 1.0 specification.

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

### Headings
```
= Heading 1
== Heading 2
=== Heading 3
==== Heading 4
===== Heading 5
====== Heading 6
```
Optional trailing equal signs are silently stripped: `== Heading ==` renders the same as `== Heading`.

### Links
```
[[http://example.com]]           — bare URL link
[[http://example.com|Click here]] — described URL link
[[PageName]]                     — wiki page link
[[PageName|display text]]        — described wiki page link
```
Links use double square brackets `[[ ]]` with pipe `|` separator for display text. Target starting with a URL scheme (`http://`, `https://`, etc.) creates an external link; otherwise creates a wiki page link.

### Images
```
{{image.png}}                    — bare image
{{image.png|alt text}}           — image with alt text
```
Images use double curly braces `{{ }}` with pipe `|` for alt text.

### Unordered Lists
```
* Item 1
* Item 2
** Nested item
*** Deeply nested
```
Character repetition indicates nesting depth.

### Ordered Lists
```
# First
# Second
## Nested numbered
```

### Tables
```
|= Header 1 |= Header 2
| Cell 1     | Cell 2
| Cell 3     | Cell 4
```
Each row starts with `|`. Header cells use `|=` prefix. Optional trailing `|` at end of row.

### Nowiki (Preformatted) — Block
```
{{{
preformatted text
no **markup** here
}}}
```
Triple curly braces on their own lines create a preformatted block.

### Nowiki — Inline
```
This is {{{**not bold**}}} text.
```
Triple curly braces inline create monospace/nowiki span.

### Line Break
```
Text before\\text after
```
Double backslash `\\` forces a line break.

### Horizontal Rule
```
----
```
Four or more dashes on their own line.

### Escape Character
```
~*not bold~*
~//not italic~//
```
Tilde `~` escapes the next special character, preventing it from being interpreted as markup.

---

## Horde Extensions Beyond Creole 1.0

The Horde Text_Wiki Creole implementation includes several extensions not part of the core Creole 1.0 specification. These come from the legacy `CreoleEngine` and its associated parser rules.

### Underline
```
__underlined text__
```
**Not in Creole 1.0.** Extension from Horde's CreoleEngine.

### Superscript
```
E=mc^^2^^
```
**Not in Creole 1.0.** Double caret delimiters.

### Subscript
```
H,,2,,O
```
**Not in Creole 1.0.** Double comma delimiters.

### Blockquote
```
> Quoted text
> More quoted text
```
**Not in Creole 1.0.** Uses `>` prefix (also `:` in legacy parser).

### Center
```
! Centered text
```
**Not in Creole 1.0.** Uses `!` prefix at line start.

### Definition List
```
; Term : Definition
; Another term : Another definition
```
**Not in Creole 1.0.** Uses `;` and `:` delimiters.

### Break (alternate)
```
%%%
```
**Not in Creole 1.0 core.** `%%%` as an alternative to `\\` for line break.

---

## Gaps with Creole 1.0 Specification

### Implemented in Horde

All Creole 1.0 core elements are supported:

| Creole 1.0 Element | Horde Status |
|---|---|
| Bold `**...**` | Supported |
| Italic `//...//` | Supported |
| Headings `= ... ======` | Supported (with trailing `=` stripping) |
| Links `[[...]]` | Supported (URL and wikilink disambiguation) |
| Images `{{...}}` | Supported |
| Unordered lists `*` | Supported (with nesting) |
| Ordered lists `#` | Supported (with nesting) |
| Tables `\|...\|` | Supported (with `\|=` headers) |
| Nowiki block `{{{ }}}` | Supported |
| Nowiki inline `{{{...}}}` | Supported |
| Line break `\\` | Supported |
| Horizontal rule `----` | Supported |
| Escape `~char` | Supported (single character escape) |

### Not Implemented / Deferred

| Feature | Reason |
|---|---|
| Placeholder extension (`<<<...>>>`) | Not in Creole 1.0 core; proposed but never standardized |
| Interwiki links (`[[WikiName:Page]]`) | Recognized by legacy parser but not modeled in typed AST |
| Footnotes (`[1]`) | Legacy extension, niche use case |
| Address/Signature (`-- text`) | Legacy extension, niche use case |
| Box (footnote container) | Legacy extension, niche use case |

### Behavioral Differences from Strict Creole 1.0

1. **Dash list marker**: The legacy Horde parser accepts `-` as a bullet list marker in addition to `*`. The modern tokenizer preserves this for backward compatibility, though `-` is not in the Creole 1.0 spec.

2. **Blockquote prefix**: Creole 1.0 does not define blockquotes. The Horde implementation uses `>` and `:` prefixes (the `:` variant renders with a "remark" CSS class in the legacy system).

3. **Heading trailing equals**: Creole 1.0 specifies that trailing `=` signs are stripped. The Horde parser implements this correctly.

4. **Escape scope**: Creole 1.0's tilde escape only escapes a single following non-whitespace character. The Horde implementation matches this behavior — there is no block-level escape mechanism like Tiki's `~np~...~/np~`.

5. **Image attributes**: Creole 1.0 only defines `src` and `alt` for images. The Horde implementation preserves these two attributes.

---

## Syntax Quick Reference

| Element | Syntax |
|---|---|
| Bold | `**text**` |
| Italic | `//text//` |
| Underline | `__text__` (extension) |
| Monospace | `{{{text}}}` |
| Superscript | `^^text^^` (extension) |
| Subscript | `,,text,,` (extension) |
| Heading 1–6 | `=` to `======` + space + text |
| Link | `[[target\|text]]` |
| Image | `{{src\|alt}}` |
| Bullet list | `*` (repeat for nesting) |
| Numbered list | `#` (repeat for nesting) |
| Table | `\| cell \| cell`, `\|= header` |
| Code block | `{{{ ... }}}` (on own lines) |
| Break | `\\` |
| Horiz rule | `----` |
| Escape | `~char` |
| Blockquote | `> text` (extension) |
| Center | `! text` (extension) |
| Deflist | `; term : def` (extension) |

---

## References

- Creole 1.0 Specification: https://www.wikicreole.org/wiki/Creole1.0
- Creole All Markup: http://www.wikicreole.org/wiki/AllMarkup
- Wikipedia: https://en.wikipedia.org/wiki/Creole_(markup)
- Implementation Guide: http://www.wikicreole.org/wiki/Implementation
- WikiSym 2007 Paper: https://www.opensym.org/ws2007/_publish/Sauer_WikiSym2007_WikiCreole.pdf
