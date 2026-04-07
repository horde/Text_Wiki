# coWiki Dialect Documentation

## Historical Context

### Origins and Development

**coWiki** (stylized as coWiki) was a PHP-based wiki engine created by **Daniel T. Gorski** starting in December 2001. It emerged during the "second generation" of wiki engines a few years after Ward Cunningham's original WikiWikiWeb (1995) but before MediaWiki solidified its dominance around 2002–2005.

- **Author:** Daniel T. Gorski
- **Development period:** 2001–2006
- **Last official release:** Version 0.3.4 (February 2005)
- **Implementation:** PHP 5
- **Project status:** Historical/archived (development ended 2006)

### Design Philosophy

Unlike many early wikis designed for radical openness coWiki was **explicitly not aimed at radical openness**. Instead, it focused on:

- **Structured collaboration** over emergent chaos
- **Security by default** rather than open-by-default
- **Governed structure** instead of free-form text fields
- **Document model** rather than simple text-to-HTML transformation

This positioned coWiki as a **wiki for organizations** rather than public communities.

### Unique Technical Architecture

coWiki's most distinctive technical feature was its **XML-first worldview**. This was highly unusual for its time at the end of the XML-everything era:

#### XML as Internal Representation

> "Documents are parsed to XML for further export/transformation."

Unlike contemporary engines (UseMod, MediaWiki) that treated wiki text as **string-to-HTML** coWiki treated wiki syntax as a **structured document language**:

1. Wiki markup → **XML intermediate representation** → Output formats
2. This enabled export and transformation pipelines (PDF, structured reuse)
3. Anticipated later ideas like:
   - MediaWiki's Parsoid HTML+DOM (2010s)
   - XWiki's XDOM
   - Pandoc-style AST pipelines (2006+)

This makes coWiki one of the earliest wiki systems to treat markup as a document model, not just formatting shortcuts.

#### Parser-as-Specification

coWiki never had a formal language specification document.

- **The parser *is* the spec**
- Each syntactic feature corresponds to a discrete parsing rule
- Syntax defined by implementation, not user convention

This architectural style resembled:
- DocBook / SGML thinking
- Later Pandoc reader backends

Most of this was rare among wikis of its era.

### Relationship to Other Wiki Engines

#### Similar to TWiki (but not derivative)

coWiki is often described as having markup "similar to TWiki" but this deserves precision:

**Similarities:**
- Explicit markup (not CamelCase-based linking)
- Table syntax using pipes
- Emphasis on predictability over cleverness

**Key Differences:**
- **TWiki** evolved toward extensive macro languages and plugin-driven syntax expansion
- **coWiki** emphasized core syntax + XML structure with minimal ad-hoc magic

The coWiki dialect is best seen as a **parallel evolution** but not a TWiki derivative.

#### Compared to WikiWikiWeb / UseMod

| Aspect | WikiWikiWeb | coWiki |
|--------|-------------|--------|
| **Linking** | CamelCase | Explicit links |
| **Openness** | Radical | Secure by default |
| **Data model** | Plain text | Parsed XML |
| **Philosophy** | Social experiment | Structured collaboration |

### Legacy and Influence

coWiki represents a **road not taken**: wikis as structured publishing systems rather than social text fields.

**Historical Significance:**
- One of the earliest **structurally-oriented** wiki languages
- Precursor to **document-model-based** wiki architecture
- Sibling—not parent—of TWiki and TikiWiki
- Conceptual ancestor of **AST-based markup processing**

**It is not the ancestor of today's dominant wiki dialects**, but it explored architectural patterns that later became mainstream.

---

## Implementation in Text_Wiki

The `Text_Wiki_Cowiki` PEAR package (now integrated into Horde's `Text_Wiki`) **explicitly re-implements coWiki syntax** to preserve this historical dialect.

### Authoritative Sources

- **PEAR Package:** [Text_Wiki_Cowiki](https://pear.php.net/package/Text_Wiki_Cowiki/docs/latest/)
- **Parser Documentation:** [Text_Wiki_Parse_Table](https://pear.php.net/package/Text_Wiki_Cowiki/docs/latest/Text_Wiki/Text_Wiki_Parse_Table.html)
- **Historical Reference:** [CoWiki on Opensource Fandom](https://opensource.fandom.com/wiki/CoWiki)
- **Modern Fork:** [eoneed/cowiki on Packagist](https://packagist.org/packages/eoneed/cowiki)

---

## coWiki Markup Syntax Reference

### Basic Formatting

#### Bold
```
*bold text*
```
**Renders as:** **bold text**

#### Italic
```
/italic text/
```
**Renders as:** *italic text*

**  Note:** Single slash `/` is used (not double). The parser uses negative lookbehind `(?<!<)` to avoid conflicts with HTML-style tags containing slashes. Italic rule must be applied **after** all HTML-style rules.

#### Underline
```
<u>underlined text</u>
```
**Renders as:** <u>underlined text</u>

#### Strong (Bold Alternative)
```
<strong>strong emphasis</strong>
```

#### Emphasis (Italic Alternative)
```
<em>emphasized text</em>
```

#### Monospace/Teletype
```
<tt>monospace text</tt>
```

#### Subscript
```
<sub>subscript</sub>
```

#### Superscript
```
<sup>superscript</sup>
```

### Headings

Headings use plus signs (`+`) at the start of a line:

```
+ Heading Level 1
++ Heading Level 2
+++ Heading Level 3
++++ Heading Level 4
```

**Format:** `^(\+)+` followed by space and heading text

### Lists

#### Unordered Lists (Bullets)
```
* Item 1
* Item 2
 * Nested item (indented with space)
* Item 3
```

#### Ordered Lists (Numbers)
```
# Item 1
# Item 2
 # Nested item (indented with space)
# Item 3
```

**Format:** Lines starting with `*` or `#` followed by space. Indentation with leading spaces creates nesting.

### Links

#### Inline URLs
```
http://example.com
https://example.com/page
mailto:user@example.com
```
**Supported schemes:** `http://`, `https://`, `ftp://`, `gopher://`, `news://`, `mailto:`

#### Described Links (Named URLs)
```
((http://example.com)(Link description))
```
**Format:** `((URL)(description text))`

If description is omitted, URL is used as text:
```
((http://example.com))
```

#### Wiki Links

**StudlyCaps/CamelCase Links:**
```
WikiPageName
StudlyCapsStyle
```

**Described Wiki Links:**
```
[WikiPageName nice text link to display]
```

**Wiki Links with Anchor:**
```
WikiPageName#section-anchor
[WikiPageName#anchor link description]
```

#### Freelinks (Free-form Page Names)
```
[[page name with spaces]]
[[Page Name|Display Text]]
```

#### Interwiki Links
```
OtherWiki:PageName
OtherWiki:PageName|Description
```
Requires interwiki map configuration.

### Tables

coWiki uses HTML-style table syntax:

```
<table>
<tr><th>Header 1</th><th>Header 2</th></tr>
<tr><td>Cell 1</td><td>Cell 2</td></tr>
<tr><td>Cell 3</td><td>Cell 4</td></tr>
</table>
```

**Supported attributes:** Tables can include HTML attributes like `border`, `cellpadding`, etc.

### Code and Preformatted Text

#### Code Blocks
```
<code>
function example() {
    return true;
}
</code>
```

#### Raw/Literal Text
```
<raw>
No wiki processing here: *bold* /italic/ WikiLink
</raw>
```

#### HTML Passthrough
```
<html>
<div class="custom">
Raw HTML content
</div>
</html>
```

### Other Elements

#### Horizontal Rule
```
<hr>
```

#### Line Break
```
<br>
```

#### Anchor (Named Target)
```
<a name="section-name"></a>
```

#### Centered Text
```
<center>Centered content</center>
```

#### Blockquotes
```
<blockquote>
Quoted text goes here.
Can span multiple lines.
</blockquote>
```

#### Definition List
```
<dl>
<dt>Term 1</dt>
<dd>Definition for term 1</dd>
<dt>Term 2</dt>
<dd>Definition for term 2</dd>
</dl>
```

#### Colored Text
```
<font color="red">Red text</font>
<font color="#0000FF">Blue text (hex)</font>
```

#### Box (Container)
```
<box>
Content in a box container
</box>
```

### Special Features

#### Include (Transclusion)
```
<include page="OtherPageName"></include>
```
Includes content from another wiki page.

#### Function Calls
```
<function name="functionName" param1="value1"></function>
```
Executes wiki functions (requires configuration).

#### Revisions/History
```
<revise>
Content marked for revision tracking
</revise>
```

#### PHP Lookup (PHP Manual Links)
```
<?php functionName ?>
```
Creates links to PHP.net documentation.

#### Delimiter
```
<delimiter>
---
</delimiter>
```

#### Embed (Object Embedding)
```
<embed src="url" type="mime/type"></embed>
```

#### Table of Contents
```
<toc></toc>
```
Auto-generates table of contents from headings.

---

## Parser Implementation Notes

### HTML-First Syntax

Unlike most wiki engines that avoid HTML, coWiki **embraces HTML-like syntax** for many elements:

- Tables: `<table>`, `<tr>`, `<td>`, `<th>`
- Formatting: `<u>`, `<em>`, `<strong>`, `<tt>`, `<sub>`, `<sup>`
- Structure: `<blockquote>`, `<dl>`, `<dt>`, `<dd>`, `<center>`
- Special: `<code>`, `<raw>`, `<html>`, `<box>`

This reflects coWiki's **XML-first philosophy** and its positioning as a **structured document system**.

### Parsing Order Matters

The italic parser (`/text/`) **must run after** HTML-style parsers to avoid:
- Matching slashes in self-closing tags: `<br />`
- Matching slashes in closing tags: `</tag>`

The parser uses negative lookbehind: `(?<!<)/text/` to prevent false matches.

### Token-Based Rendering

Like other Text_Wiki dialects, Cowiki:

1. **Parses** source to identify markup patterns
2. **Tokenizes** matched elements with metadata
3. **Renders** tokens to target format (XHTML, LaTeX, Plain, etc.)

This three-phase architecture allows the same parser to produce multiple output formats.

---

## Comparison to Other Dialects

| Feature | coWiki | MediaWiki | TikiWiki | Default |
|---------|---------|-----------|----------|---------|
| **Bold** | `*text*` | `'''text'''` | `__text__` | `'''text'''` |
| **Italic** | `/text/` | `''text''` | `''text''` | `''text''` |
| **Heading L1** | `+ Text` | `= Text =` | `! Text` | `+ Text` |
| **Unordered List** | `* Item` | `* Item` | `* Item` | `* Item` |
| **Ordered List** | `# Item` | `# Item` | `# Item` | `# Item` |
| **Links** | `((url)(text))` | `[url text]` | `[url\|text]` | `[url text]` |
| **Wiki Links** | `WikiName` or `[[Name]]` | `[[Name]]` | `((Name))` | `WikiName` |
| **Tables** | `<table>...</table>` | `{\| ... \|}` | `\|\|cell\|\|` | `\|\| cell \|\| cell \|\|` |
| **Code** | `<code>...</code>` | `<code>...</code>` | `{CODE()}...{CODE}` | `<code>...</code>` |

**Key Distinctions:**
- coWiki's **HTML-like syntax** for structural elements is unique
- **Described links** use double parens: `((url)(text))`
- **Single-character bold** (`*`) and **italic** (`/`) differ from most wikis
- **Plus-sign headings** match Default but with different philosophy

---

## Summary

coWiki represents an important but often-overlooked branch of wiki engine evolution:

- **Document-model first:** XML as intermediate representation (2001)
- **Structured over social:** Governed collaboration vs. radical openness
- **HTML-influenced syntax:** Embraced tags where others avoided them
- **Parser-as-spec:** Implementation-defined language

The `Text_Wiki_Cowiki` implementation preserves this historical dialect and demonstrates alternative approaches to wiki markup that anticipated modern structured-document pipelines.

**Further Reading:**
- [coWiki on Tiki.org](https://doc.tiki.org)
- [Original PEAR Package Documentation](https://pear.php.net/package/Text_Wiki_Cowiki/docs/latest/)
- [Opensource Wiki History](https://opensource.fandom.com/wiki/CoWiki)
