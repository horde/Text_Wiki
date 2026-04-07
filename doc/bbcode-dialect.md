# BBCode Dialect Documentation

**BBCode** (Bulletin Board Code) is a lightweight markup language used to format posts in online forums and message boards.

---

## Table of Contents
- [History and Background](#history-and-background)
- [Design Philosophy](#design-philosophy)
- [Syntax Overview](#syntax-overview)
- [Tag Reference](#tag-reference)
- [Implementation Notes](#implementation-notes)
- [Comparison with Other Formats](#comparison-with-other-formats)
- [Sources](#sources)

---

## History and Background

### Origins

**BBCode was first introduced in 1998** as bulletin board forum software became popular on the web. Early forums allowed users to format their posts using raw HTML. However this created significant security vulnerabilities:

- **Security risks**: Users could inject JavaScript code
- **Layout disruption**: Malicious HTML could break forum page layouts
- **XSS attacks**: Cross-site scripting vulnerabilities were common
- **Administration burden**: Moderators had to police HTML usage

### Creation Purpose

BBCode was created as a **safer alternative to HTML** that still allowed users to format their posts with common styling options. The key innovation was using square brackets `[tag]` instead of angle brackets `<tag>`:

1. **Easier to parse and sanitize**
2. **Impossible to inject raw HTML/JavaScript**
3. **Simpler for non-technical users to learn**
4. **More controlled** - administrators could enable/disable specific tags

### Adoption

BBCode became widely adopted by popular forum software:

- **phpBB** - One of the earliest and most popular implementations
- **vBulletin** - Commercial forum software with extensive BBCode support
- **Invision Power Board (IPB)** - Another major forum platform
- **MyBB** - Free forum software with BBCode
- **SMF (Simple Machines Forum)** - Uses BBCode extensively

By the mid-2000s, BBCode had become the de facto standard for forum post formatting.

### Evolution

Over time, BBCode implementations added features beyond basic text formatting:

- **Media embedding**: YouTube videos, audio players
- **Tables**: Structured data presentation
- **Spoiler tags**: Hide content until clicked
- **Enhanced quotes**: Attributed quotes with author names
- **Custom extensions**: Forum-specific tags like `[member=123]`

---

## Design Philosophy

### Core Principles

1. **Safety First**: Cannot inject executable code or raw HTML
2. **User-Friendly**: Tags are descriptive and easy to remember
3. **Predictable**: Explicit open/close tags like HTML
4. **Controllable**: Administrators can selectively enable features
5. **Extensible**: New tags can be added for specific needs

### Tag Structure

BBCode follows a consistent pattern:

```
[tagname]content[/tagname]
[tagname=parameter]content[/tagname]
```

**Characteristics:**
- Square brackets (not angle brackets)
- Explicit closing tags with `/`
- Case-insensitive (usually - implementation-dependent)
- Parameters use `=` assignment
- Nested tags are supported

### Advantages Over HTML

| Feature | BBCode | HTML |
|---------|--------|------|
| Security | Safe - no script injection | Dangerous if not sanitized |
| Learning curve | Easy for beginners | Requires technical knowledge |
| Typing speed | Faster (shorter tags) | Slower (verbose) |
| Administrator control | Granular tag permissions | All-or-nothing |
| User errors | Forgiving | Breaks page layout easily |

---

## Syntax Overview

### Basic Formatting

```bbcode
[b]bold text[/b]
[i]italic text[/i]
[u]underlined text[/u]
[s]strikethrough text[/s]
```

### Nested Formatting

```bbcode
[b][i]bold and italic[/i][/b]
[u][color=red]red underline[/color][/u]
```

### Parameterized Tags

```bbcode
[size=20]large text[/size]
[color=blue]blue text[/color]
[url=http://example.com]link text[/url]
[quote=John]John's words[/quote]
```

### Case Insensitivity

Most BBCode implementations treat tags as case-insensitive:

```bbcode
[b]bold[/b]
[B]bold[/B]
[Bold]bold[/Bold]  
```

All three typically render the same way though lowercase is conventional.

---

## Tag Reference

### Text Formatting

#### Bold
```bbcode
[b]bold text[/b]
```
**Output:** **bold text**

#### Italic  
```bbcode
[i]italic text[/i]
```
**Output:** *italic text*

#### Underline
```bbcode
[u]underlined text[/u]
```
**Output:** <u>underlined text</u>

#### Strikethrough
```bbcode
[s]crossed out text[/s]
```
**Output:** ~~crossed out text~~

#### Superscript
```bbcode
E=mc[sup]2[/sup]
```
**Output:** E=mc²

#### Subscript
```bbcode
H[sub]2[/sub]O
```
**Output:** H₂O

---

### Colors and Fonts

#### Color by Name
```bbcode
[color=red]red text[/color]
[color=blue]blue text[/color]
[color=green]green text[/color]
```

**Supported colors (common):**
- red, blue, green, yellow, orange, purple, pink
- black, white, gray, silver
- brown, cyan, magenta, lime

#### Color by Hex Code
```bbcode
[color=#FF0000]red text[/color]
[color=#00FF00]green text[/color]
[color=#0000FF]blue text[/color]
```

#### Font Family
```bbcode
[font=Arial]Arial text[/font]
[font=Courier]Courier text[/font]
[font=Times New Roman]Times text[/font]
```

#### Font Size
```bbcode
[size=10]small text[/size]
[size=14]normal text[/size]
[size=20]large text[/size]
```

Size is typically in points (pt) or pixels, depending on implementation.

---

### Links and Images

#### Simple URL
```bbcode
[url]http://example.com[/url]
```
**Output:** The URL is clickable with the URL as the visible text.

#### URL with Custom Text
```bbcode
[url=http://example.com]Click here[/url]
```
**Output:** "Click here" appears as a clickable link.

#### Email
```bbcode
[email]user@example.com[/email]
[email=user@example.com]Email me[/email]
```

#### Image
```bbcode
[img]http://example.com/image.jpg[/img]
```
**Output:** The image is displayed inline.

#### Image with Size
```bbcode
[img=300x200]http://example.com/image.jpg[/img]
```
Resizes the image to 300 pixels wide by 200 pixels tall.

#### Linked Image
```bbcode
[url=http://example.com][img]http://example.com/image.jpg[/img][/url]
```
The image becomes a clickable link.

---

### Lists

#### Unordered List (Bullets)
```bbcode
[list]
[*]First item
[*]Second item
[*]Third item
[/list]
```

**Output:**
- First item
- Second item
- Third item

#### Ordered List (Numbered)
```bbcode
[list=1]
[*]First item
[*]Second item
[*]Third item
[/list]
```

**Output:**
1. First item
2. Second item
3. Third item

#### Ordered List (Lettered)
```bbcode
[list=a]
[*]First item
[*]Second item
[*]Third item
[/list]
```

**Output:**
a. First item
b. Second item
c. Third item

#### Nested Lists
```bbcode
[list]
[*]Outer item one
[list]
[*]Nested item one
[*]Nested item two
[/list]
[*]Outer item two
[/list]
```

**Note:** Some implementations use `[ul]`/`[ol]` instead of `[list]`, and `[li]` instead of `[*]`.

---

### Quotes and Code

#### Simple Quote
```bbcode
[quote]
This is a quote from someone.
[/quote]
```

**Output:** The text appears in a quote box or with quote styling.

#### Attributed Quote
```bbcode
[quote=John Doe]
This is what John said.
[/quote]
```

**Output:** Quote box shows "John Doe said:" followed by the content.

#### Nested Quotes
```bbcode
[quote=Alice]
I said something
[quote=Bob]
Bob replied to me
[/quote]
Back to Alice's words
[/quote]
```

#### Code Block
```bbcode
[code]
function example() {
    return true;
}
[/code]
```

**Output:** Content displayed in monospace font, preserving whitespace and preventing BBCode parsing within.

#### Code with Language
```bbcode
[code=php]
<?php
echo "Hello World";
?>
[/code]
```

Some implementations support syntax highlighting when a language is specified.

#### Preformatted Text
```bbcode
[pre]
  Line breaks and    spaces
      are preserved exactly
[/pre]
```

---

### Alignment

#### Center
```bbcode
[center]Centered text[/center]
```

#### Left
```bbcode
[left]Left-aligned text[/left]
```

#### Right
```bbcode
[right]Right-aligned text[/right]
```

---

### Tables

#### Basic Table
```bbcode
[table]
[tr]
[th]Header 1[/th]
[th]Header 2[/th]
[/tr]
[tr]
[td]Cell 1[/td]
[td]Cell 2[/td]
[/tr]
[/table]
```

**Tags:**
- `[table]` - Table container
- `[tr]` - Table row
- `[th]` - Table header cell
- `[td]` - Table data cell

**Note:** Table support varies widely across BBCode implementations. Many forums don't support tables at all.

---

### Advanced/Extended Tags

#### Spoiler
```bbcode
[spoiler]Hidden content revealed on click[/spoiler]
[spoiler=Click to reveal]Hidden content[/spoiler]
```

#### YouTube Video
```bbcode
[youtube]dQw4w9WgXcQ[/youtube]
```

Embeds the YouTube video with the given ID.

#### Horizontal Rule
```bbcode
[hr]
```

Inserts a horizontal line/divider.

---

## Implementation Notes

### PEAR Text_Wiki2 BBCode Implementation

The PEAR Text_Wiki2 library includes a BBCode parser with the following parsers:

**Available (12 parsers):**
- Bold: `[b]...[/b]`
- Italic: `[i]...[/i]`
- Underline: `[u]...[/u]`
- Superscript: `[sup]...[/sup]`
- Subscript: `[sub]...[/sub]`
- Code: `[code]...[/code]`
- Blockquote: `[quote]...[/quote]`
- List: `[list][*]...[/list]`
- Image: `[img]...[/img]`
- URL: `[url]...[/url]`
- Colortext: `[color=...]...[/color]`
- Font: `[font=...]...[/font]`

**Default Parser Order:**
1. Prefilter, Delimiter
2. Code (runs early to protect content)
3. Blockquote, List, Image
4. Url, Colortext, Font
5. Bold, Italic, Underline
6. Superscript, Subscript
7. Tighten (cleanup)

**Key Implementation Details:**
- Uses regex pattern: `#\[tag](.*?)\[/tag]#i` (case-insensitive)
- List parser uses recursive regex for nested lists: `#\[list(?:=(.+?))?]\n?((?:((?R))|.)*?)\[/list]\n?#msi`
- URL parser includes auto-detection of bare URLs
- Code blocks protect content from further parsing

### Horde Text_Wiki BBCode

The Horde Text_Wiki implementation (PSR-4 conversion of PEAR Text_Wiki2):

**Status:**
- All 12 parsers ported
- BBCodeEngine with default rule set
- Uses standard Xhtml/Plain renderers
- No BBCode-to-BBCode renderer (for idempotency)
- Test coverage needs to be improved

**File Locations:**
- Engine: `Horde\Text\Wiki\BBCodeEngine`
- Parsers: `Horde\Text\Wiki\BBCodeParser*`
- Example: `Horde\Text\Wiki\BBCodeParserBold`

---

## Comparison with Other Formats

### BBCode vs HTML

| Feature | BBCode | HTML |
|---------|--------|------|
| Tag style | `[b]text[/b]` | `<b>text</b>` |
| Security | Safe by design | Requires sanitization |
| Case sensitivity | Usually insensitive | Case-insensitive |
| Learning curve | Easy | Moderate |
| Expressiveness | Limited (by design) | Full control |
| Use case | User-generated content | Web development |

### BBCode vs Markdown

| Feature | BBCode | Markdown |
|---------|--------|----------|
| Bold | `[b]text[/b]` | `**text**` |
| Italic | `[i]text[/i]` | `*text*` |
| Links | `[url=...]text[/url]` | `[text](url)` |
| Learning | Explicit tags | Natural text |
| Verbosity | More verbose | Minimal |
| Ambiguity | None (explicit) | Some (context) |
| Adoption | Forums | Documentation, GitHub |

### BBCode vs Wiki Markup

| Feature | BBCode | Wiki (MediaWiki) |
|---------|--------|------------------|
| Bold | `[b]text[/b]` | `'''text'''` |
| Italic | `[i]text[/i]` | `''text''` |
| Links | `[url=...]text[/url]` | `[[Page|text]]` |
| Philosophy | Safety-focused | Feature-rich |
| Complexity | Simple | Complex |
| Extensions | Forum-specific | Wiki-specific |

### BBCode vs Cowiki

| Feature | BBCode | Cowiki |
|---------|--------|--------|
| Bold | `[b]text[/b]` | `*text*` |
| Italic | `[i]text[/i]` | `/text/` |
| Headings | Not standard | `+ Heading` |
| Lists | `[list][*]item[/list]` | `* item` or `# item` |
| Links | `[url=x]text[/url]` | `((url)(text))` |
| Tables | `[table]` syntax | HTML syntax |
| Philosophy | Forum posts | Wiki pages |

---

## Extended Features to Consider

Based on bbcode.org reference and common forum implementations, these tags could be added:

### Not Yet Implemented

**Strikethrough:**
```bbcode
[s]text[/s]
```

**Alignment tags:**
```bbcode
[center]text[/center]
[left]text[/left]
[right]text[/right]
```

**Size tag:**
```bbcode
[size=14]text[/size]
```


**Spoiler tag:**
```bbcode
[spoiler]hidden text[/spoiler]
```

**Horizontal rule:**
```bbcode
[hr]
```

**Email tag:**
```bbcode
[email]user@example.com[/email]
```

**Alternative list syntax:**
```bbcode
[ul][li]item[/li][/ul]
[ol][li]item[/li][/ol]
```

### Out of scope

**Table tags:**
```bbcode
[table][tr][th]Header[/th][td]Cell[/td][/tr][/table]
```

Table support across bbcode products is too fragmented and non-standardized. It is up to an integrator to implement his preferred brand of table tags.

---

## Sources

- BBCode.org main site: https://www.bbcode.org/
- BBCode.org tag reference: https://www.bbcode.org/reference.php
- PEAR Text_Wiki2 BBCode implementation: https://github.com/pear/Text_Wiki2
- Horde Text_Wiki implementation: Modern PSR-4 conversion

---

## See Also

- [Default (YaWiki) Dialect](default-dialect.md)
- [Cowiki Dialect](cowiki-dialect.md)
- [TikiWiki Dialect](tikiwiki-dialect.md)
