# MediaWiki Markup Dialect Documentation

## Historical Context

### Origins: Wikipedia's Engine

**MediaWiki** is the wiki engine behind Wikipedia and all Wikimedia Foundation projects.
It grew out of a series of wiki software iterations created to serve the burgeoning Wikipedia community.

- **Project Name:** MediaWiki
- **First Release:** 2003 (as "Phase III")
- **Predecessor:** UseModWiki (Phase I, 2001), Magnus Manske's PHP wiki (Phase II, 2002)
- **Key Developers:** Magnus Manske (Phase II), Lee Daniel Crocker (Phase III rewrite), Brion Vibber, Tim Starling
- **Current Status:** Active open-source project
- **License:** GPL-2.0
- **Website:** https://www.mediawiki.org

### Evolution

1. **Phase I (2001):** Wikipedia launched on UseModWiki, a Perl-based wiki engine by Clifford Adams. Its syntax (CamelCase links, `'''bold'''` apostrophe markup) set the foundation.
2. **Phase II (2002):** Magnus Manske rewrote the software in PHP to handle Wikipedia's growing traffic. Added namespaces, user pages, and the `[[wikilink]]` double-bracket syntax.
3. **Phase III (2002–2003):** Lee Daniel Crocker performed a major rewrite for scalability. This became "MediaWiki" proper. Brion Vibber and Tim Starling became long-term core developers.
4. **MediaWiki 1.x (2003–present):** Continued evolution with parser improvements, extension framework, Lua scripting (Scribunto), and Visual Editor.

### Design Philosophy

MediaWiki's syntax philosophy prioritizes **backward compatibility and casual authoring**:

1. **Minimal markup for common tasks** — bold/italic use apostrophes, links use brackets
2. **HTML subset allowed** — trusted HTML tags work directly in wikitext
3. **Backward compatibility** — syntax changes are extremely conservative due to millions of existing pages
4. **Extension over syntax inflation** — new features via parser extensions, not core syntax changes
5. **Table syntax** — powerful but complex `{| |- | ! |}` syntax for structured data

### Syntax Heritage

MediaWiki's markup sits in a unique position:

- **UseModWiki** — inherited apostrophe-based bold/italic (`'''bold'''`, `''italic''`)
- **Pre-Markdown era** — MediaWiki's syntax predates Markdown's popularity
- **HTML integration** — unlike most wiki syntaxes, MediaWiki allows a subset of HTML directly
- **Extensible** — ParserFunctions, templates, Lua modules extend the syntax dynamically

### Adoption

MediaWiki is the most widely deployed wiki engine:
- Wikipedia and all Wikimedia projects (500+ wikis)
- Enterprise documentation (Fandom/Wikia, Miraheze)
- Software documentation (Arch Wiki, Gentoo Wiki)
- Academic and research wikis

---

## MediaWiki Core Syntax

### Bold and Italic (Apostrophe State Machine)

```
''italic text''
'''bold text'''
'''''bold italic'''''
```

MediaWiki uses an apostrophe-counting state machine:
- `''` (2 apostrophes) toggles italic
- `'''` (3 apostrophes) toggles bold
- `'''''` (5 apostrophes) toggles both
- `''''` (4 apostrophes) = 1 literal apostrophe + bold toggle

This is one of the most complex inline parsing rules in any wiki syntax.

### Headings

```
= Heading 1 =
== Heading 2 ==
=== Heading 3 ===
==== Heading 4 ====
===== Heading 5 =====
====== Heading 6 ======
```

**Important:** Unlike DokuWiki, MediaWiki headings are **NOT inverted** — `=` count equals the heading level directly. `=` = H1, `======` = H6.

### Links

```
[[Page Name]]                    — wiki page link
[[Page Name|display text]]       — described wiki page link
[[Page#section|anchor link]]     — link with anchor
[http://example.com text]        — external link with text
http://example.com               — bare URL (auto-linked)
[[mailto:user@example.com|text]] — email link
```

### Images

```
[[File:image.png]]               — bare image
[[File:image.png|alt text]]      — image with alt text
[[Image:photo.jpg|caption]]      — Image: prefix also works
```

Both `File:` and `Image:` prefixes are recognized (case-insensitive).

### Unordered Lists

```
* Item 1
* Item 2
** Nested item
*** Deeply nested
```

Character repetition determines nesting level: `*` = level 1, `**` = level 2.

### Ordered Lists

```
# First
# Second
## Nested numbered
```

`#` for numbered items, with character repetition for nesting.

### Mixed Lists

```
* Bullet
*# Numbered inside bullet
*#* Bullet inside numbered inside bullet
```

### Tables

```
{| class="wikitable"
|+ Caption text
|-
! Header 1 !! Header 2
|-
| Cell 1 || Cell 2
|-
| Cell 3 || Cell 4
|}
```

MediaWiki table syntax:
- `{|` opens a table (optional attributes)
- `|}` closes a table
- `|-` separates rows (optional row attributes)
- `!` marks header cells, `!!` separates multiple headers
- `|` marks data cells, `||` separates multiple cells
- `|+` creates a caption

### Definition Lists

```
; Term
: Definition
; Term : Inline definition
```

`;` marks terms, `:` marks definitions. Can be on separate lines or inline with ` : ` separator.

### Code Block

```
<code>
preformatted code
</code>
```

Optional language: `<code php>`.

### Preformatted Text

```
<pre>
preformatted text
</pre>
```

Also, lines starting with a space are automatically preformatted:
```
 This line starts with a space
 So does this one
```

### Nowiki (Unformatted)

```
<nowiki>'''not bold''' [[not a link]]</nowiki>
```

Prevents wiki markup interpretation inside the tags.

### Horizontal Rule

```
----
```

Four or more dashes on their own line.

### Line Break

```
Text<br />more text
```

HTML-style `<br />` tag.

### Blockquote

```
<blockquote>Quoted text</blockquote>
```

HTML-style blockquote tags.

### HTML Subset Tags

MediaWiki allows these HTML tags directly in wikitext:

```
<sup>superscript</sup>
<sub>subscript</sub>
<u>underlined</u>
<tt>monospace</tt>
<s>strikethrough</s>
<del>deleted</del>
<ins>inserted</ins>
```

### Text Alignment

```
<div style="text-align:center;">Centered text</div>
<div style="text-align:left;">Left text</div>
<div style="text-align:right;">Right text</div>
<div style="text-align:justify;">Justified text</div>
```

### Text Styling

```
<span style="color:red;">Red text</span>
<span style="font-family:serif;">Serif text</span>
<span style="font-size:120%;">Larger text</span>
```

### Anchors

```
<span id="my-anchor"></span>
```

Headings also automatically create anchors from their text.

### Table of Contents

```
__TOC__              — force TOC at this position
__NOTOC__            — suppress TOC
__FORCETOC__         — force TOC even with fewer than 4 headings
```

### Comments

```
<!-- This is hidden from output -->
```

HTML comments are stripped from rendered output.

---

## Horde Implementation Details

### Key Syntax Differences from Other Dialects

| Feature | MediaWiki | DokuWiki | Creole |
|---|---|---|---|
| Bold | `'''text'''` | `**text**` | `**text**` |
| Italic | `''text''` | `//text//` | `//text//` |
| Heading direction | `=` = H1 (normal) | `======` = H1 (inverted) | `=` = H1 (normal) |
| List nesting | `*`/`#` repetition | 2-space indentation | `*`/`#` repetition |
| Header cells | `!` prefix | `^` delimiter | `\|=` prefix |
| Monospace | `<tt>text</tt>` | `''text''` | `{{{text}}}` |
| Code block | `<code>...</code>` | `<code>...</code>` | `{{{ }}}` |
| Nowiki | `<nowiki>...</nowiki>` | `%%text%%` / `<nowiki>` | `{{{text}}}` |
| Tables | `{| |- \| ! \|}` | `\| ^ delimiters` | `\| \|= per row` |
| Strikethrough | `<s>text</s>` | `<del>text</del>` | N/A |
| Break | `<br />` | `\\` | `\\` |
| Images | `[[File:img\|alt]]` | `{{img\|alt}}` | `{{img\|alt}}` |
| Wikilinks | `[[Page\|text]]` | `[[Page\|text]]` | `[[Page\|text]]` |
| Alignment | `<div style="text-align:X">` | N/A | `! text` (center) |
| Color | `<span style="color:X">` | N/A | N/A |
| Email | `[[mailto:x@y\|text]]` | N/A | N/A |
| YouTube | Well-known URL | N/A | N/A |
| TOC | `__TOC__` magic words | N/A | N/A |

---

## Gaps with MediaWiki Specification

### Implemented in Horde

The most commonly used MediaWiki elements are supported:

| MediaWiki Element | Horde Status |
|---|---|
| Bold `'''...'''` | Supported (apostrophe state machine) |
| Italic `''...''` | Supported |
| Bold+Italic `'''''...'''''` | Supported |
| Headings `= ... ======` | Supported (level = `=` count, NOT inverted) |
| Wikilinks `[[Page\|text]]` | Supported (page, anchor, display text) |
| External URLs `[url text]` | Supported (described and bare) |
| Images `[[File:img\|alt]]` | Supported (File: and Image: prefixes) |
| Email `[[mailto:...\|text]]` | Supported |
| YouTube (well-known URL) | Supported (configurable domains) |
| Unordered lists `*` | Supported (character-repetition nesting) |
| Ordered lists `#` | Supported |
| Mixed lists `*#` | Supported |
| Tables `{| ... \|}` | Supported (headers `!`, data `\|`, rows `\|-`)  - Horde does handle a bare minimum subset of Mediawiki's powerful table syntax to provide a migration target for older wiki markups. It's not even trying to achieve feature parity with the Mediawiki implementation. |
| Definition lists `;`/`:` | Supported (inline and multi-line) |
| Code `<code>...</code>` | Supported (with optional language) |
| Pre `<pre>...</pre>` | Supported |
| Nowiki `<nowiki>...</nowiki>` | Supported (block and inline) |
| Horizontal rule `----` | Supported |
| Break `<br />` | Supported |
| Blockquote `<blockquote>` | Supported |
| Superscript `<sup>` | Supported |
| Subscript `<sub>` | Supported |
| Underline `<u>` | Supported |
| Monospace `<tt>` | Supported |
| Strikethrough `<s>` | Supported |
| Deleted `<del>` | Supported |
| Inserted `<ins>` | Supported |
| Center alignment | Supported (`<div style="text-align:center">`) |
| Left/Right/Justify | Supported |
| Color/Font/Size | Supported (`<span style="...">`) |
| Anchors `<span id="...">` | Supported |
| TOC `__TOC__` | Supported (magic words) |
| Comments `<!-- -->` | Supported (stripped) |
| Space-indented preformatted | Supported |

### Not Implemented / Deferred

| Feature | Reason |
|---|---|
| Templates `{{template}}` | Complex macro system, requires template engine |
| Parser functions `{{#if:}}` | Requires template engine |
| Categories `[[Category:Name]]` | Metadata system, not rendered |
| Redirects `#REDIRECT [[Page]]` | Server-side behavior |
| Interwiki links `[[wp:Page]]` | Requires interwiki map configuration - best handled at application level |
| Magic words (beyond TOC) | `__NOTOC__`, `__FORCETOC__` recognized but not fully processed |
| Table captions `\|+` | Recognized but caption text deferred |
| Cell attributes (colspan/rowspan) | Deferred for simplicity |
| Table nesting | Recursive tables deferred |
| Image attributes (dimensions, thumb, frame) | Only src/alt supported |
| Gallery `<gallery>` | Extension feature |
| Math `<math>` | Extension feature |
| Syntax highlighting `<syntaxhighlight>` | Extension feature |
| References `<ref>` / `<references>` | Extension feature |
| Infoboxes, navboxes | Template-based, require template engine |
| Freelink | Not a MediaWiki feature (explicit `[[link]]` only) |
| PHP lookup | Application-level (Wicked), not parser-level |

### Behavioral Differences from Standard MediaWiki

1. **Apostrophe state machine**: The Horde implementation uses a simplified state machine that processes text line-by-line. It correctly handles `''`, `'''`, `''''`, and `'''''` sequences. Edge cases with ambiguous nesting (e.g., mixed bold/italic across multiple apostrophe sequences) may resolve differently from the canonical MediaWiki parser.

2. **Table parsing**: The Horde implementation splits tables by `|-` row separators and processes cell lines individually. Nested tables (`{|` inside `{|`) are not supported. Cell attributes (before `|` in a cell) are recognized and stripped.

3. **Space-indented preformatted**: Lines starting with a single space are grouped into `<pre>` blocks, matching MediaWiki behavior. The leading space is stripped.

4. **YouTube detection**: Not part of standard MediaWiki — this is a Horde extension. YouTube URLs from configurable domains are automatically converted to embedded `<iframe>` players. Domains are configurable via parser constructor options.

5. **HTML subset**: The parser recognizes a specific set of HTML tags (`sup`, `sub`, `u`, `tt`, `s`, `del`, `ins`, `br`, `blockquote`, `code`, `pre`, `nowiki`). Arbitrary HTML is not supported — only these whitelisted tags are processed.

6. **Style attributes**: Color, font, and size are extracted from `<span style="...">` tags. Only the specific property patterns are matched — general CSS is not parsed.

7. **Comments**: HTML comments (`<!-- ... -->`) are completely stripped during preprocessing, before any tokenization occurs.

---

## Syntax Quick Reference

| Element | Syntax |
|---|---|
| Bold | `'''text'''` |
| Italic | `''text''` |
| Bold+Italic | `'''''text'''''` |
| Underline | `<u>text</u>` |
| Monospace | `<tt>text</tt>` |
| Superscript | `<sup>text</sup>` |
| Subscript | `<sub>text</sub>` |
| Strikethrough | `<s>text</s>` |
| Deleted | `<del>text</del>` |
| Inserted | `<ins>text</ins>` |
| Heading 1 (largest) | `= text =` |
| Heading 6 (smallest) | `====== text ======` |
| Wikilink | `[[target\|text]]` |
| External URL | `[url text]` |
| Image | `[[File:src\|alt]]` |
| Email | `[[mailto:addr\|text]]` |
| Bullet list | `*` (repetition for nesting) |
| Numbered list | `#` (repetition for nesting) |
| Table open | `{|` |
| Table row | `\|-` |
| Table header | `! cell !! cell` |
| Table data | `\| cell \|\| cell` |
| Table close | `\|}` |
| Definition term | `; term` |
| Definition def | `: definition` |
| Code block | `<code>` ... `</code>` |
| Preformatted | `<pre>` ... `</pre>` |
| Nowiki | `<nowiki>` ... `</nowiki>` |
| Break | `<br />` |
| Horiz rule | `----` |
| Blockquote | `<blockquote>` ... `</blockquote>` |
| Center | `<div style="text-align:center;">` |
| Color | `<span style="color:red;">` |
| Font | `<span style="font-family:serif;">` |
| Size | `<span style="font-size:14pt;">` |
| Anchor | `<span id="name"></span>` |
| TOC | `__TOC__` |
| Comment | `<!-- hidden -->` |

---

## References

- MediaWiki Official Website: https://www.mediawiki.org
- MediaWiki Wikitext Reference: https://www.mediawiki.org/wiki/Help:Wikitext
- MediaWiki Formatting: https://www.mediawiki.org/wiki/Help:Formatting
- Wikipedia: https://en.wikipedia.org/wiki/MediaWiki
- MediaWiki Source Code: https://github.com/wikimedia/mediawiki
