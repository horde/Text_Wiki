# Default / YaWiki Dialect Documentation

## Historical Context

### Origins: YaWiki (Yet Another Wiki)

The **Default** dialect in `Text_Wiki` originates from **YaWiki** (Yet Another Wiki) which was created by **Paul M. Jones** in the early 2000s as part of his exploration of wiki engines and markup patterns.

- **Original Author:** Paul M. Jones
- **Project:** YaWiki (Yet Another Wiki)
- **Time period:** Early 2000s (circa 2002-2004)
- **Philosophy:** Balanced, practical wiki markup combining best practices from multiple sources

### Design Goals

Paul M. Jones designed YaWiki's markup to be:

1. **Intuitive:** Easy to learn and remember
2. **Balanced:** Not too minimal, not too complex
3. **Readable:** Source text should be pleasant to read even unparsed
4. **Unambiguous:** Clear syntax with minimal edge cases
5. **Flexible:** Support both simple and complex document structures

The Default dialect became the **reference implementation** for the Text_Wiki PEAR package:
- A **baseline** for other dialect parsers to extend or override
- A **fallback** when specific dialect features aren't needed
- A **teaching tool** demonstrating clean wiki markup principles

---

## Relationship to Other Wikis

### Influences

The Default dialect drew inspiration from:

- **WikiWikiWeb** (Ward Cunningham, 1995): CamelCase linking
- **UseMod Wiki** (Clifford Adams, 1999-2000): Simple markup patterns
- **MoinMoin** (2000): Structured lists and tables
- **TWiki** (1998-2000): Emphasis on explicit syntax

### Key Differences from Contemporaries

| Feature | Default (YaWiki) | MediaWiki | TWiki |
|---------|------------------|-----------|-------|
| **Bold** | `'''text'''` | `'''text'''` | `*text*` |
| **Italic** | `''text''` | `''text''` | `_text_` |
| **Heading** | `+ Text` (prefix) | `= Text =` (balanced) | `---+ Text` |
| **Tables** | `\|\| cell \|\| cell \|\|` | `{\| ... \|}` | `\| cell \| cell \|` |
| **Wikilinks** | `WikiName` or `[WikiName]` | `[[WikiName]]` | `WikiName` |

**Default's design choices:**
- **Triple quotes** for bold/italic (like MediaWiki) rather than single chars
- **Plus-prefix headings** (simpler than balanced markup)
- **Double-pipe tables** (clearer visual alignment)
- **Both CamelCase and explicit links** (flexibility)

---

## Default Markup Syntax Reference

### Basic Formatting

#### Bold
```
'''bold text'''
```
**Renders as:** **bold text**

#### Italic  
```
''italic text''
```
**Renders as:** *italic text*

#### Combined Bold-Italic
```
'''''bold and italic'''''
```
**Renders as:** ***bold and italic***

#### Underline
```
__underlined text__
```
**Renders as:** <u>underlined text</u>

#### Monospace/Teletype
```
{{monospace text}}
```
**Renders as:** `monospace text`

#### Superscript
```
text^superscript^
```
**Renders as:** text<sup>superscript</sup>

#### Subscript
```
text,,subscript,,
```
**Renders as:** text<sub>subscript</sub>

#### Colortext
```
##red|red text##
##FF0000|hex color##
```
**Format:** `##color|text##` where color is a name or hex code

### Headings

Headings use plus signs at the start of a line:

```
+ Heading Level 1
++ Heading Level 2
+++ Heading Level 3
++++ Heading Level 4
+++++ Heading Level 5
++++++ Heading Level 6
```

**Format:** `^(\+{1,6}) (.+)$` - plus signs followed by space and heading text

### Paragraphs

Paragraphs are separated by blank lines:

```
First paragraph text.

Second paragraph text.
```

**No explicit markup needed** - blank lines indicate paragraph breaks.

### Lists

#### Unordered Lists (Bullets)
```
* Item 1
* Item 2
** Nested item (two asterisks)
** Another nested item
* Item 3
```

#### Ordered Lists (Numbers)
```
# Item 1
# Item 2
## Nested numbered item
## Another nested item
# Item 3
```

#### Mixed Nesting
```
* Bullet item
*# Numbered sub-item
*# Another numbered sub-item
* Another bullet
```

**Format:** Repeat `*` or `#` to increase nesting level. Must be at start of line.

### Links

#### Inline URLs
```
http://example.com
https://example.com/page
ftp://ftp.example.com
mailto:user@example.com
```

**Supported schemes:** `http://`, `https://`, `ftp://`, `gopher://`, `news://`, `mailto:`

#### Numbered Reference Links
```
[http://example.com]
```
Creates a footnote-style numbered link: [1]

#### Described Links
```
[http://example.com Example Site]
```
**Format:** `[URL description text]`

#### Wiki Links

**CamelCase (StudlyCaps):**
```
WikiPageName
StudlyCapsLink
```
Automatically creates links to wiki pages.

**Explicit Wiki Links:**
```
[WikiPageName]
[WikiPageName Display Text]
[WikiPageName#anchor]
[WikiPageName#anchor Display Text]
```

#### Freelinks (Free-form Page Names)
```
((page name with spaces))
((Page Name With Spaces|Display Text))
```

#### Interwiki Links
```
Wikipedia:Article_Name
OtherWiki:PageName
```
Requires interwiki map configuration.

### Tables

Tables use double pipes (`||`) to separate cells:

```
|| Header 1 || Header 2 || Header 3 ||
|| Cell 1 || Cell 2 || Cell 3 ||
|| Cell 4 || Cell 5 || Cell 6 ||
```

**First row** is automatically treated as headers.

**Aligned content:**
```
||~ Header (center) ||< Left ||> Right ||
|| Normal || Left-aligned || Right-aligned ||
```

**Column spanning:**
```
|| Normal || Spans two columns ||| ||
```

### Code and Preformatted Text

#### Inline Code
```
Some {{inline code}} in text.
```

#### Code Blocks
```
<code>
function example() {
    return true;
}
</code>
```

Preserves whitespace and prevents wiki markup processing.

### Blockquotes

```
> This is a quoted line.
> This is another quoted line.
>> Nested quote (two angle brackets)
```

**Format:** Lines starting with `>` - increase angle brackets for nesting.

### Horizontal Rules

```
----
```

Four or more hyphens create a horizontal rule.

### Anchors (Named Targets)

```
# AnchorName

Link to: [#AnchorName Jump to anchor]
```

**Format:** `# ` followed by anchor name on its own line.

### Images

```
[[image.jpg]]
[[image.jpg alt text]]
[[http://example.com/image.png]]
```

**With attributes:**
```
[[image.jpg|alt text|class="photo"|width="300"]]
```

### Definition Lists

```
: Term 1 : Definition for term 1
: Term 2 : Definition for term 2
: Term 3 : Multiple definitions
:: Nested definition
```

**Format:** `: Term : Definition` - colons at start of line.

### Special Elements

#### Center Text
```
= Centered text =
```

**Format:** Text between equals signs on its own line.

#### PHP Function Lookup
```
<?php functionName ?>
```
Creates links to PHP.net documentation for the function.

#### Raw/Literal Text (No Processing)
```
<raw>
No wiki markup processed: '''bold''' ''italic'' WikiLink
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

#### Table of Contents
```
[[toc]]
```
Auto-generates table of contents from headings.

#### Smiley/Emoticon Conversion
```
:)  :-)  ;)  :(  :P  :D
```
Converts common emoticons to emoji or images (if enabled).

#### Revision Tracking
```
[edit:username:timestamp]
Text that was edited
[/edit]
```
Marks editorial changes with metadata.

---

## Parser Implementation Architecture

### Three-Phase Processing

The Default dialect (like all Text_Wiki parsers) uses a **three-phase architecture**:

1. **Parse Phase:** Identify markup patterns and create tokens
   - Each rule (bold, italic, heading, etc.) scans for its pattern
   - Matched text is replaced with delimited tokens containing metadata
   - Original content preserved in token data

2. **Token Phase:** Tokens stored with structured data
   - Each token has: `rule name`, `type`, `options` (href, text, level, etc.)
   - Tokens are placeholders: `\x00~TOKEN_ID~\x00`

3. **Render Phase:** Tokens converted to target format
   - XHTML renderer: outputs HTML5-compatible markup
   - LaTeX renderer: outputs LaTeX commands
   - Plain renderer: strips formatting for plain text
   - Custom renderers: can target any format

This architecture allows:
- **Same parser, multiple outputs** (XHTML, LaTeX, RTF, Plain, etc.)
- **Extensibility** - add new renderers without changing parsers
- **Composability** - rules can be enabled/disabled independently

### Rule Precedence

Parse rules execute in a specific order to handle ambiguity:

1. **Escape/Raw** - processed first to prevent wiki processing
2. **Prefilter** - preprocessing before main parsing
3. **Delimiter** - block-level structure
4. **Code** - protect code blocks from other rules
5. **Html** - HTML passthrough
6. **Tables** - large block structures
7. **Lists** - block-level lists
8. **Blockquotes** - block-level quotes
9. **Headings** - block-level headings
10. **Horiz** - horizontal rules
11. **Deflist** - definition lists
12. **Table** - alternative table syntax
13. **Image** - image embedding
14. **Phplookup** - PHP function links
15. **Center** - centered text
16. **Url** - URL links
17. **Freelink** - free-form page names
18. **Interwiki** - interwiki links
19. **Wikilink** - CamelCase and explicit links
20. **Colortext** - colored text
21. **Strong/Bold** - bold formatting
22. **Emphasis/Italic** - italic formatting
23. **Underline** - underlined text
24. **Tt/Monospace** - monospaced text
25. **Superscript** - superscript
26. **Subscript** - subscript
27. **Revise** - revision marks
28. **Tightenlines** - whitespace cleanup
29. **Break** - manual line breaks
30. **Paragraph** - paragraph wrapping

**Why order matters:** Earlier rules can "protect" content from later rules. For example, content inside `<code>` blocks won't be processed by bold/italic rules.

---

## Configuration Options

The Default dialect supports various configuration options:

### Wikilink Options
```php
$wiki->setFormatConf('Wikilink', 'ext_chars', true);  // Allow extended charset
$wiki->setFormatConf('Wikilink', 'utf-8', true);       // UTF-8 support
```

### URL Schemes
```php
$wiki->setParseConf('Url', 'schemes', [
    'http://', 'https://', 'ftp://', 'mailto:', 'custom://'
]);
```

### Interwiki Maps
```php
$wiki->setRenderConf('xhtml', 'wikilink', [
    'view_url' => 'https://wiki.example.com/%s',
    'new_url' => 'https://wiki.example.com/create?page=%s',
]);

$wiki->setRenderConf('xhtml', 'interwiki', [
    'Wikipedia' => 'https://en.wikipedia.org/wiki/%s',
    'GitHub' => 'https://github.com/%s',
]);
```

### Table of Contents
```php
$wiki->setFormatConf('Toc', 'title', 'Table of Contents');
$wiki->setFormatConf('Toc', 'div_id', 'toc');
```

---

## Comparison to Other Dialects

### vs. MediaWiki

**Similarities:**
- Triple quotes for bold/italic
- CamelCase and explicit wiki links
- Similar URL handling

**Differences:**
- Headings: `+ Text` vs. `= Text =`
- Tables: `|| cell ||` vs. `{| ... |}`
- Simpler syntax overall

### vs. Markdown

**Similarities:**
- Readable plain text
- Simple list syntax
- Code blocks

**Differences:**
- Markdown: `**bold**` → Default: `'''bold'''`
- Markdown: `*italic*` → Default: `''italic''`
- Markdown: `#` headings → Default: `+` headings
- Default has wiki-specific features (CamelCase, interwiki)

### vs. coWiki

**Similarities:**
- Plus-sign headings
- Support for HTML elements
- Structured parsing

**Differences:**
- coWiki: HTML-first syntax (`<table>`, `<u>`, etc.)
- Default: Wiki-style syntax with minimal HTML
- coWiki: `*bold*` and `/italic/` vs Default: `'''bold'''` and `''italic''`

---

## Use Cases

The Default dialect is ideal for:

1. **General-purpose wikis** - balanced feature set
2. **Documentation systems** - readable source, clean output
3. **Content management** - flexible linking and formatting
4. **Multi-format publishing** - same source to XHTML, LaTeX, Plain
5. **Teaching/learning** - demonstrates clean wiki markup principles

---

## Legacy and Influence

Paul M. Jones' Default dialect served as:

- **Foundation** for Text_Wiki PEAR package
- **Reference implementation** showing clean parser design
- **Educational tool** for understanding wiki markup
- **Practical solution** balancing power and simplicity

While not as widely adopted as MediaWiki syntax, the Default dialect:

- Influenced other wiki engines exploring "middle-ground" markup
- Demonstrated **composable parser architecture** (rule-based tokenization)
- Provided **multi-format rendering** before Pandoc made it mainstream
- Showed that **well-designed defaults matter** in markup languages

---

## Further Reading

- **Text_Wiki PEAR Documentation:** http://pear.php.net/package/Text_Wiki
- **Paul M. Jones' Writing:** http://paul-m-jones.com
- **Wiki Markup Comparison:** Various sources comparing wiki dialects
- **Text_Wiki Source Code:** Horde implementation at `~/php/git/horde/Text_Wiki/`

---

## Summary

The **Default dialect** represents a **thoughtful synthesis** of early wiki markup practices:

- **Balanced syntax** - not too simple, not too complex
- **Readable source** - plain text should look good
- **Extensible architecture** - rule-based, multi-format rendering
- **Practical design** - real-world usage informed syntax choices

It may not have the name recognition of MediaWiki or the ubiquity of Markdown, but it demonstrates **principled design** in markup languages and remains a **solid choice** for wiki-style text processing.
