# AST Element Type Reference

Canonical reference for the typed AST element vocabulary used by all parsers
and renderers.  Consult this document **before** adding a new element type to
avoid introducing duplicates.

---

## How to use this document

1. You are implementing a parser or renderer and need an element for "inline
   code" — search this page for the concept.  You will find `tt` is the
   canonical element.  Use it; do not invent `codespan`, `monospace`, or
   `inlinecode`.

2. You find two elements that appear identical — check the "Known duplicates"
   section.  If they are listed there the duplication is acknowledged and the
   canonical name is indicated.  Prefer the canonical name in new code.

3. You need a new concept that is not listed — add it to this document first,
   with its semantic meaning and expected rendering, before writing parser or
   renderer code.

As a rule of thumb: Think in semantic meaning, not in renderer appearance.

---

## Canonical element types

### Inline formatting

| Element | Semantic meaning | XHTML | LaTeX | Docbook |
|---------|-----------------|-------|-------|---------|
| `bold` | Strong importance (visual bold) | `<strong>` | `\textbf{}` | `<emphasis role="bold">` |
| `italic` | Stress emphasis (visual italic) | `<em>` | `\textit{}` | `<emphasis>` |
| `underline` | Underlined text | `<u>` | `\underline{}` | `<emphasis role="underline">` |
| `tt` | Inline code / monospace text | `<code>` | `\texttt{}` | `<code>` |
| `strike` | Struck-through text (visible deletion) | `<del>` | `\sout{}` | `<emphasis role="strikethrough">` |
| `del` | Deleted content (revision tracking) | `<del>` | `\sout{}` | `<emphasis role="deleted">` |
| `ins` | Inserted content (revision tracking) | `<ins>` | `\underline{}` | `<emphasis role="inserted">` |
| `subscript` | Subscript positioning | `<sub>` | `\textsubscript{}` | `<subscript>` |
| `superscript` | Superscript positioning | `<sup>` | `\textsuperscript{}` | `<superscript>` |
| `break` | Hard line break | `<br />` | `\\` | `<?linebreak?>` |
| `softbreak` | Soft line break (renderer decides) | `\n` | `\n` | `\n` |

### Links and references

| Element | Semantic meaning | Attributes |
|---------|-----------------|------------|
| `url` | External hyperlink | `href`, `title` |
| `wikilink` | Internal wiki page link | `page`, `anchor` |
| `email` | Email address link | `email` |
| `image` | Image embed | `src`, `alt`, `title` |
| `phplookup` | PHP manual function link | `function` |
| `anchor` | Named anchor point (link target) | `name` |

### Block elements

| Element | Semantic meaning |
|---------|-----------------|
| `heading` | Section heading (attribute: `level` 1–6) |
| `paragraph` | Paragraph block |
| `code` | Fenced/indented code block (attribute: `language`) |
| `blockquote` | Block quotation (container — holds child blocks) |
| `horiz` | Thematic break / horizontal rule |
| `list` | List container (attributes: `type` bullet/ordered, `start`) |
| `listitem` | List item (attribute: `tight` for CommonMark tight lists) |
| `table` | Table container |
| `row` | Table row |
| `cell` | Table cell (attributes: `type` header/data, `align`) |
| `deflist` | Definition list container |
| `defterm` | Definition term |
| `defdef` | Definition description |
| `htmlblock` | Raw HTML block (verbatim passthrough) |
| `preformatted` | Preformatted text block (no syntax highlighting) |
| `raw` | Raw/unprocessed content block |

### Inline raw

| Element | Semantic meaning |
|---------|-----------------|
| `htmlinline` | Raw inline HTML (verbatim passthrough) |

### Visual styling (no semantic meaning)

| Element | Semantic meaning | Attributes |
|---------|-----------------|------------|
| `color` | Colored text | `color` |
| `font` | Font family override | `font` |
| `size` | Font size override | `size` |
| `center` | Center-aligned block | — |
| `left` | Left-aligned block | — |
| `right` | Right-aligned block | — |
| `justify` | Justified block | — |

### Metadata / structural

| Element | Semantic meaning |
|---------|-----------------|
| `toc` | Table-of-contents placeholder |
| `youtube` | YouTube video embed (child text = video ID) |

---

## Known duplicates and their resolution

Elements that exist under multiple names due to independent parser development.
The **canonical** name is the one new code should use.  Existing parsers may
still emit the legacy name; renderers should handle both until migration is
complete.

### `bold` / `strong` — SAME MEANING

Both mean "strong importance / visual bold."

| Renderer | `bold` | `strong` |
|----------|--------|----------|
| Xhtml | `<strong>` | `<strong>` |
| Latex | `\textbf{}` | `\textbf{}` |
| Docbook | `<emphasis role="bold">` | `<emphasis role="strong">` |

Docbook uses different `role` values but the rendering is identical.
The HTML distinction between `<b>` (presentational) and `<strong>` (semantic)
is not meaningful in the AST — all parsers that produce these elements intend
strong emphasis.

**Canonical name: `bold`** — used by 6 parsers (BBCode, Cowiki, Creole, Doku,
Tiki, Yawiki).  `strong` is used only by Mediawiki and Yawiki as an alias.

**Parsers that emit `strong`:** Mediawiki, Yawiki.
**Parsers that emit `bold`:** BBCode, Cowiki, Creole, Doku, Tiki, Yawiki, Markdown.

### `italic` / `emphasis` — SAME MEANING

Both mean "stress emphasis / visual italic."

| Renderer | `italic` | `emphasis` |
|----------|----------|------------|
| Xhtml | `<em>` | `<em>` |
| Latex | `\textit{}` | `\textsl{}` |
| Docbook | `<emphasis role="italic">` | `<emphasis>` |

LaTeX uses `\textit` vs `\textsl` (italic vs slanted) but the intent is the
same and the visual difference is negligible.  Docbook differs in role but
both produce emphasis.

**Canonical name: `italic`** — used by 6 parsers.  `emphasis` is used only by
Mediawiki and Yawiki as an alias.

**Parsers that emit `emphasis`:** Mediawiki, Yawiki.
**Parsers that emit `italic`:** BBCode, Cowiki, Creole, Doku, Tiki, Yawiki, Markdown.

### `tt` / `codespan` — RESOLVED

Both meant "inline code / monospace text."  **Unified to `tt`.**

`codespan` was used only by the Markdown parser.  As of the duplicate
unification, all parsers now emit `tt` and all renderers handle `tt`.
The Xhtml renderer was updated to emit `<code>` instead of the deprecated
`<tt>` HTML tag.

**No action needed** — `codespan` is no longer emitted by any parser or
handled by any renderer.

### `del` / `strike` / `revise_del` — OVERLAPPING MEANING

All three represent deleted/struck-through content but have different origins:

- `strike` — visual strikethrough (BBCode `[s]`, GFM `~~`)
- `del` — semantic deletion mark (Doku, Mediawiki `<del>`)
- `revise_del` — revision-tracked deletion (Yawiki `@@---text@@`)

| Renderer | `strike` | `del` | `revise_del` |
|----------|----------|-------|-------------|
| Xhtml | `<del>` | `<del>` | `<del>` |
| Latex | `\sout{}` | `\sout{}` | `\sout{}` |
| Docbook | `<emphasis role="strikethrough">` | `<emphasis role="deleted">` | `<emphasis role="deleted">` |

`strike` and `del` render identically everywhere except Docbook (different role
text, same visual result).  `revise_del` also renders identically but carries
revision-tracking semantics in the Yawiki renderer (paired with `revise_ins`
via sibling lookahead).

**These should remain separate** because `revise_del` has structural pairing
behavior in Yawiki.  However, `strike` and `del` are candidates for
unification.

**Canonical name for simple strikethrough: `strike`** — the more widely
understood term.  `del` should be reserved for cases where the parser
explicitly distinguishes semantic deletion from visual strikethrough (currently
only Doku and Mediawiki, where `<del>` tags are parsed literally).

### `ins` / `revise_ins` — OVERLAPPING MEANING

Same situation as `del` / `revise_del`:

- `ins` — semantic insertion mark (Mediawiki `<ins>`)
- `revise_ins` — revision-tracked insertion (Yawiki `@@+++text@@`)

| Renderer | `ins` | `revise_ins` |
|----------|-------|-------------|
| Xhtml | `<ins>` | `<ins>` |
| Latex | `\underline{}` | `\underline{}` |
| Docbook | `<emphasis role="inserted">` | `<emphasis role="inserted">` |

Identical rendering everywhere.  `revise_ins` carries pairing semantics in
Yawiki.

**These should remain separate** for the same structural reason as
`del`/`revise_del`.

### `wikilink` / `freelink` — RESOLVED

Both meant "internal wiki page link."  **Unified to `wikilink`.**

`freelink` was a Yawiki-specific syntax (`((Page Name))`).  As of the duplicate
unification, the Yawiki parser emits `wikilink` and all `renderFreelink` methods
have been removed from renderers.

**No action needed** — `freelink` is no longer emitted by any parser or
handled by any renderer.

### `color` / `colortext` — RESOLVED

Both meant "colored text" with a `color` attribute.  **Unified to `color`.**

`colortext` was used by Tiki and Yawiki parsers.  As of the duplicate
unification, both parsers emit `color` and all `renderColortext` methods have
been removed from renderers.

**No action needed** — `colortext` is no longer emitted by any parser or
handled by any renderer.

### `code` / `raw` / `preformatted` — DIFFERENT MEANINGS

These are **not** duplicates despite superficial similarity:

- `code` — source code block with optional syntax highlighting (`language`
  attribute).  Renders as `<pre><code>` in XHTML.
- `preformatted` — preformatted text without code semantics.  Renders as
  `<pre>` in XHTML (no `<code>` wrapper).
- `raw` — unprocessed content passed through verbatim.  Semantic intent varies
  by source format.

**These should remain separate.**

### `horiz` / `hr` — RESOLVED

Both meant "thematic break / horizontal rule."  **Unified to `horiz`.**

`hr` existed only as a fallback alias in the Markdown renderer.  The alias has
been removed.

**No action needed** — `hr` is no longer handled by any renderer.

---

## Element-to-parser matrix

Which parsers emit which elements.  Use this to verify coverage when adding a
new renderer.

| Element | BBCode | Markdown | Cowiki | Creole | Doku | Mediawiki | Tiki | Yawiki |
|---------|--------|----------|--------|--------|------|-----------|------|--------|
| anchor | x | | | | | x | x | x |
| blockquote | x | x | x | x | x | x | x | x |
| bold | x | x | x | x | x | | x | x |
| break | | x | | x | x | x | x | x |
| cell | | x | x | x | x | x | x | x |
| center | x | | | x | x | x | x | x |
| code | x | x | x | x | x | x | x | x |
| color | x | | | | | x | x | x |
| defdef | | | | x | x | x | x | x |
| deflist | | | | x | x | x | x | x |
| defterm | | | | x | x | x | x | x |
| del | | | | | x | x | | |
| email | x | | | | | x | | |
| emphasis | | | | | | x | | x |
| font | x | | | | | x | | |
| heading | | x | x | x | x | x | x | x |
| horiz | x | x | x | x | x | x | x | x |
| htmlblock | | x | | | | | | |
| htmlinline | | x | | | | | | |
| image | x | x | | x | x | x | x | x |
| ins | | | | | | x | | |
| italic | x | x | x | x | x | | x | x |
| justify | x | | | | | x | | |
| left | x | | | | | x | | |
| list | x | x | x | x | x | x | x | x |
| listitem | x | x | x | x | x | x | x | x |
| paragraph | | x | x | x | x | x | x | x |
| phplookup | | | | | | | | x |
| preformatted | | | | | | x | | |
| raw | | | x | x | x | x | x | |
| revise\_del | | | | | | | | x |
| revise\_ins | | | | | | | | x |
| right | x | | | | | x | | |
| row | | x | x | x | x | x | x | x |
| size | x | | | | | x | | |
| softbreak | | x | | | | | | |
| strike | x | x | | | | x | | |
| strong | | | | | | x | | x |
| subscript | x | | x | x | x | x | x | x |
| superscript | x | | x | x | x | x | x | x |
| table | | x | x | x | x | x | x | x |
| toc | | | x | | | x | x | x |
| tt | | x | x | x | x | x | x | x |
| underline | x | | x | x | x | x | x | x |
| url | x | x | x | x | x | x | x | x |
| wikilink | | | x | x | x | x | x | x |
| youtube | x | | | | | x | | |

---

## Adding a new element type

1. **Search this document** for the concept you need.  If it exists, use the
   canonical name.
2. **Check the duplicate list** — if a similar element exists under a different
   name, use the canonical one.
3. **Define the element** in this document with: semantic meaning, expected
   attributes, and expected rendering in at least XHTML, LaTeX, and Docbook.
4. **Then** implement the parser tokenizer and renderer methods.

Do not add elements that differ only in surface syntax.  `**bold**` (Markdown),
`'''bold'''` (Yawiki), `[b]bold[/b]` (BBCode) are all the same `bold` element.
The parser handles syntax; the AST is syntax-agnostic.
