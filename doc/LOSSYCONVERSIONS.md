# Lossy Conversions Reference

This document catalogues which format transitions in Horde Text\_Wiki preserve
all markup semantics and which lose information because the target format lacks
an equivalent construct.

Every conversion goes through a shared typed AST.  A **parser** turns source
text into AST nodes; a **renderer** turns AST nodes into output text.  Loss
happens when the renderer encounters an AST element it has no native syntax for
and must either drop the wrapper (emitting only the children's text) or
approximate it with a different construct.

The fallback in every renderer is `renderChildren()` — the wrapper is silently
stripped and the inner content survives.  This is *lossy* because a subsequent
parse of the output will not reconstruct the original wrapper.

See [ASTELEMENTS.md](ASTELEMENTS.md) for the canonical element type reference,
known duplicates, and guidance on adding new element types.

> **Note on duplicates:** Several elements previously existed under multiple
> names due to independent parser development (e.g. `bold`/`strong`,
> `italic`/`emphasis`).  The following duplicates have been **resolved** by
> unifying to canonical names: `codespan`→`tt`, `freelink`→`wikilink`,
> `colortext`→`color`, `hr`→`horiz`.  The remaining unresolved duplicates
> (`bold`/`strong`, `italic`/`emphasis`) still inflate the loss counts below —
> a renderer that handles `bold` but not `strong` does not truly lose strong
> emphasis, it just uses a different method name for the same concept.
> See ASTELEMENTS.md § "Known duplicates" for the full list and canonical names.

---

## Parsers and the elements they produce

| Parser     | Format key   | Elements |
|------------|-------------|----------|
| BBCode     | `bbcode`    | anchor, blockquote, bold, center, code, color, email, font, horiz, image, italic, justify, left, list, listitem, right, size, strike, subscript, superscript, underline, url, youtube |
| Markdown   | `markdown`  | blockquote, bold, break, cell, code, heading, horiz, htmlblock, htmlinline, image, italic, list, listitem, paragraph, row, softbreak, strike, table, tt, url |
| Cowiki     | `cowiki`    | blockquote, bold, cell, code, heading, horiz, italic, list, listitem, paragraph, raw, row, subscript, superscript, table, toc, tt, underline, url, wikilink |
| Creole     | `creole`    | blockquote, bold, break, cell, center, code, defdef, deflist, defterm, heading, horiz, image, italic, list, listitem, paragraph, raw, row, subscript, superscript, table, tt, underline, url, wikilink |
| Doku       | `doku`      | blockquote, bold, break, cell, center, code, defdef, deflist, defterm, del, heading, horiz, image, italic, list, listitem, paragraph, raw, row, subscript, superscript, table, tt, underline, url, wikilink |
| Mediawiki  | `mediawiki` | anchor, blockquote, break, cell, center, code, color, defdef, deflist, defterm, del, email, emphasis, font, heading, horiz, image, ins, justify, left, list, listitem, paragraph, preformatted, raw, right, row, size, strike, strong, subscript, superscript, table, toc, tt, underline, url, wikilink, youtube |
| Tiki       | `tiki`      | anchor, blockquote, bold, break, cell, center, code, color, defdef, deflist, defterm, heading, horiz, image, italic, list, listitem, paragraph, raw, row, subscript, superscript, table, toc, tt, underline, url, wikilink |
| Yawiki     | `yawiki`    | anchor, blockquote, bold, break, cell, center, code, color, defdef, deflist, defterm, emphasis, heading, horiz, image, italic, list, listitem, paragraph, phplookup, revise\_del, revise\_ins, row, strong, subscript, superscript, table, toc, tt, underline, url, wikilink |

---

## Renderers and the elements they handle

| Renderer   | Format key   | Explicit render methods |
|------------|-------------|------------------------|
| BBCode     | `bbcode`    | blockquote, bold, center, code, color, email, font, horiz, image, italic, justify, left, list, listitem, paragraph, right, size, strike, subscript, superscript, underline, url, youtube |
| Cowiki     | `cowiki`    | blockquote, bold, code, heading, horiz, italic, list, listitem, paragraph, raw, row, subscript, superscript, table, toc, tt, underline, url, wikilink |
| Creole     | `creole`    | blockquote, bold, break, center, code, deflist, heading, horiz, image, italic, list, listitem, paragraph, raw, row, subscript, superscript, table, tt, underline, url, wikilink |
| Docbook    | `docbook`   | anchor, blockquote, bold, break, cell, center, code, color, deflist, del, email, emphasis, font, heading, horiz, image, ins, italic, justify, left, list, listitem, paragraph, phplookup, preformatted, raw, revise\_del, revise\_ins, right, row, size, strike, strong, subscript, superscript, table, toc, tt, underline, url, wikilink, youtube |
| Doku       | `doku`      | blockquote, bold, break, center, code, deflist, del, heading, horiz, image, italic, list, listitem, paragraph, raw, row, subscript, superscript, table, tt, underline, url, wikilink |
| Latex      | `latex`     | anchor, blockquote, bold, break, center, code, color, deflist, del, email, emphasis, font, heading, horiz, image, ins, italic, justify, left, list, listitem, paragraph, phplookup, preformatted, raw, revise\_del, revise\_ins, right, row, size, strike, strong, subscript, superscript, table, toc, tt, underline, url, wikilink, youtube |
| Markdown   | `markdown`  | anchor, blockquote, bold, break, center, code, color, deflist, del, email, emphasis, font, heading, horiz, htmlblock, htmlinline, image, ins, italic, justify, left, list, listitem, paragraph, phplookup, preformatted, raw, revise\_del, revise\_ins, right, size, softbreak, strike, strong, subscript, superscript, table, toc, tt, underline, url, wikilink, youtube |
| Mediawiki  | `mediawiki` | anchor, blockquote, bold, break, cell, center, code, color, defdef, deflist, defterm, del, email, emphasis, font, heading, horiz, htmlblock, htmlinline, image, ins, italic, justify, left, list, listitem, paragraph, phplookup, preformatted, raw, revise\_del, revise\_ins, right, row, size, softbreak, strike, strong, subscript, superscript, table, toc, tt, underline, url, wikilink, youtube |
| Plain      | `plain`     | anchor, blockquote, bold, center, code, color, deflist, email, emphasis, font, heading, horiz, image, italic, justify, left, list, listitem, paragraph, phplookup, raw, revise\_del, revise\_ins, right, row, size, strike, strong, subscript, superscript, table, toc, tt, underline, url, wikilink, youtube |
| Tiki       | `tiki`      | anchor, blockquote, bold, break, center, code, color, deflist, heading, horiz, image, italic, list, listitem, paragraph, raw, row, subscript, superscript, table, toc, tt, underline, url, wikilink |
| Xhtml      | `xhtml`     | anchor, blockquote, bold, break, cell, center, code, color, deflist, del, email, emphasis, font, heading, horiz, htmlblock, htmlinline, image, ins, italic, justify, left, list, listitem, paragraph, phplookup, preformatted, revise\_del, revise\_ins, right, row, size, softbreak, strike, strong, subscript, superscript, table, toc, tt, underline, url, wikilink, youtube |
| Yawiki     | `yawiki`    | anchor, blockquote, bold, break, center, code, color, deflist, emphasis, heading, horiz, image, italic, list, listitem, paragraph, phplookup, revise\_del, revise\_ins, row, strong, subscript, superscript, table, toc, tt, underline, url, wikilink |

---

## Lossless conversions (same-format round-trips)

A **lossless** conversion preserves every semantic element.  This is only
guaranteed when the renderer handles every element the parser can produce.
Idempotent round-trips (parse → render → parse → render stabilizes) have been
verified for the pairs marked with **tested**.

| Source parser | Target renderer | Status | Notes |
|---------------|----------------|--------|-------|
| Markdown → Markdown | **Lossless, tested** | Full idempotency verified (35 tests, 3-pass stability) |
| Yawiki → Yawiki     | **Lossless, tested** | Full idempotency verified (45 tests) |
| Mediawiki → Mediawiki | Lossless | All parser elements have renderer methods |
| Creole → Creole     | Lossless | All parser elements have renderer methods |
| Doku → Doku         | Lossless | All parser elements have renderer methods |
| Cowiki → Cowiki      | Lossless | All parser elements have renderer methods |
| Tiki → Tiki         | Lossless | All parser elements have renderer methods |
| BBCode → BBCode     | Lossless | All parser elements have renderer methods |

---

## Cross-format loss matrix

For every parser → renderer pair where the source format is different from the
target, the table below lists the AST elements that the parser can produce but
the renderer has **no explicit handler** for.  These elements fall through to
`renderChildren()` — the wrapper is stripped and only the text content survives.

An empty cell means no elements are lost (the conversion is lossless).

### Wiki-format parsers → wiki-format renderers

| Source ↓ / Target → | BBCode | Cowiki | Creole | Doku | Mediawiki | Tiki | Yawiki |
|---|---|---|---|---|---|---|---|
| **BBCode** | — | anchor, color, email, font, image, size, strike, youtube | anchor, color, email, font, size, strike, youtube | anchor, color, email, font, size, strike, youtube | | anchor, color, email, font, size, strike, youtube | anchor, color, email, font, size, strike, youtube |
| **Cowiki** | cell, heading, raw, row, table, toc, tt, wikilink | — | cell, row | cell, row | | | cell, row |
| **Creole** | cell, deflist, heading, raw, row, table, tt, wikilink | break, center, deflist, image, wikilink | — | break, center | break | break | break, deflist |
| **Doku** | cell, del, deflist, heading, raw, row, table, tt, wikilink | break, center, del, deflist, image, wikilink | del | — | | break, del | break, del, deflist |
| **Mediawiki** | cell, del, deflist, heading, ins, preformatted, raw, row, strong, emphasis, table, toc, tt, wikilink | break, center, color, del, deflist, emphasis, font, image, ins, justify, left, preformatted, right, size, strike, strong, wikilink, youtube | center, color, del, emphasis, font, ins, justify, left, preformatted, right, size, strike, strong, youtube | center, color, emphasis, font, ins, justify, left, preformatted, right, size, strike, strong, youtube | — | break, color, del, emphasis, font, ins, justify, left, preformatted, right, size, strike, strong, youtube | break, color, del, deflist, font, ins, justify, left, preformatted, right, size, strike, youtube |
| **Tiki** | cell, deflist, heading, raw, row, table, toc, tt, wikilink | break, center, color, deflist, image, wikilink | color | break, color | break, color | — | break, color, deflist, wikilink |
| **Yawiki** | cell, color, deflist, emphasis, heading, phplookup, revise\_del, revise\_ins, row, strong, table, toc, tt, wikilink | break, center, color, emphasis, image, phplookup, revise\_del, revise\_ins, strong | break, color, emphasis, phplookup, revise\_del, revise\_ins, strong | break, color, emphasis, phplookup, revise\_del, revise\_ins, strong | break, color | break, color, emphasis, phplookup, revise\_del, revise\_ins, strong | — |

### Wiki-format parsers → presentation renderers

| Source ↓ / Target → | Docbook | Latex | Markdown | Plain | Xhtml |
|---|---|---|---|---|---|
| **BBCode** | | | | | |
| **Cowiki** | | | | | |
| **Creole** | | | | break | |
| **Doku** | | | | break | |
| **Mediawiki** | | | | break | |
| **Tiki** | | | | break | |
| **Yawiki** | | | | break | |

### Markdown parser → all renderers

| Target renderer | Lost elements |
|-----------------|--------------|
| BBCode | cell, heading, htmlblock, htmlinline, paragraph, row, softbreak, table |
| Cowiki | break, cell, htmlblock, htmlinline, image, paragraph, row, softbreak, strike, table |
| Creole | cell, htmlblock, htmlinline, paragraph, row, softbreak, strike, table |
| Docbook | htmlblock, htmlinline, softbreak |
| Doku | cell, htmlblock, htmlinline, paragraph, row, softbreak, strike, table |
| Latex | htmlblock, htmlinline, softbreak |
| Markdown | — (**lossless**) |
| Mediawiki | — (**lossless**) |
| Plain | break, cell, htmlblock, htmlinline, row, softbreak, table |
| Tiki | cell, htmlblock, htmlinline, paragraph, row, softbreak, strike, table |
| Xhtml | — (**lossless**) |
| Yawiki | cell, htmlblock, htmlinline, row, softbreak, strike, table |

---

## Element-level loss categories

When an element is "lost", the actual effect depends on the element type:

### Wrapper loss (formatting stripped, text preserved)

The element's children are rendered as plain text without the wrapper.
Semantic meaning is lost but content survives.

| Element | What is lost | Affected renderers |
|---------|--------------|--------------------|
| bold / strong | Emphasis styling | — (universally supported) |
| italic / emphasis | Emphasis styling | — (universally supported) |
| strike | Strikethrough | Cowiki, Creole, Tiki |
| underline | Underline styling | — (universally supported) |
| subscript | Subscript positioning | — (universally supported) |
| superscript | Superscript positioning | — (universally supported) |
| del / ins | Revision markers | Cowiki, Creole, Tiki |
| revise\_del / revise\_ins | Revision tracking | BBCode, Cowiki, Creole, Doku, Tiki |
| color / font / size | Visual styling | Cowiki, Creole, Doku |
| center / left / right / justify | Alignment | Cowiki, Doku |
| emphasis / strong | Alt emphasis names | BBCode, Cowiki, Creole, Doku, Tiki |

### Structural loss (block structure flattened)

The element's hierarchical structure is simplified or lost.

| Element | What is lost | Affected renderers |
|---------|--------------|--------------------|
| heading | Section hierarchy | BBCode |
| paragraph | Paragraph boundaries | BBCode, Cowiki |
| table / row / cell | Tabular layout | BBCode, Cowiki (partial) |
| deflist / defterm / defdef | Definition structure | BBCode, Cowiki, Yawiki (partial) |
| blockquote | Quotation nesting | — (universally supported) |

### Semantic loss (link/reference information dropped)

The element carries metadata (URLs, page names) that cannot be reconstructed.

| Element | What is lost | Affected renderers |
|---------|--------------|--------------------|
| wikilink | Internal page reference | BBCode |
| phplookup | PHP function lookup link | BBCode, Cowiki, Creole, Doku, Tiki |
| email | Email address link | Cowiki, Creole, Doku, Tiki, Yawiki |
| youtube | Video embed | Cowiki, Creole, Doku, Tiki, Yawiki |
| anchor | Named anchor point | Cowiki, Creole, Doku |
| toc | Table of contents | BBCode, Doku |

### Format-specific loss (CommonMark/GFM elements)

Elements that exist only in the Markdown AST and have no equivalent in wiki
formats.

| Element | What is lost | Affected renderers |
|---------|--------------|--------------------|
| htmlblock | Raw HTML block passthrough | All except Markdown, Mediawiki, Xhtml |
| htmlinline | Raw inline HTML passthrough | All except Markdown, Mediawiki, Xhtml |
| softbreak | Soft line break semantics | All except Markdown, Mediawiki, Xhtml |

---

## Practical guidance

### Inherently lossless paths

These conversions preserve all markup semantics because the target format's
renderer covers every element the source parser can produce:

- **Any parser → Xhtml**: XHTML is the universal output format; its renderer
  handles every known element type.
- **Any parser → Mediawiki**: The Mediawiki renderer covers every element from
  all parsers.  CommonMark-specific elements (htmlblock, htmlinline, softbreak)
  pass through as raw HTML or equivalent MediaWiki markup.
- **Any parser → Docbook / Latex**: These presentation renderers cover all
  elements from all parsers.
- **Any parser → Markdown**: The Markdown renderer covers all elements from all
  parsers.  Wiki-specific elements (wikilink, anchor, etc.) are
  approximated as Markdown links — structurally equivalent but the wiki-specific
  semantics (page resolution, anchor targets) are flattened to URLs.
- **Same-format round-trips**: Every format can losslessly round-trip through
  its own parser and renderer (see the lossless table above).

### Most lossy paths

- **Mediawiki → Cowiki**: 18 elements lost (the richest parser meets
  a limited wiki renderer).
- **Mediawiki → BBCode**: 14 elements lost.
- **Yawiki → BBCode**: 14 elements lost.
- **Markdown → BBCode**: 8 elements lost (CommonMark-specific elements have no
  BBCode equivalent).

### Recommendations

1. **For maximum fidelity**, render to Xhtml, Mediawiki, Docbook, Latex, or
   Markdown.  These renderers have the broadest element coverage.
2. **For wiki-to-wiki conversion**, expect inline formatting to survive but
   advanced features (definition lists, revision marks, coloured text, raw HTML)
   to degrade.
3. **Round-trip stability** (idempotency) is only guaranteed for same-format
   conversions.  Cross-format round-trips will converge to the intersection of
   both formats' capabilities.
4. **The `setElementHandler()` API** on every renderer allows users to plug
   custom handlers for any element, closing specific gaps without modifying
   library code.
