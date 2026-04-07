# TikiWiki Dialect Documentation

## Historical Context

### Origins: Tiki Wiki CMS Groupware

**TikiWiki** (now officially **Tiki Wiki CMS Groupware** or shortened to **Tiki**) is a free and open-source wiki-based content management system written in PHP.

- **Project Name:** Tiki Wiki CMS Groupware
- **Started:** 2002
- **Current Status:** Active development (2002–present)
- **Original Authors:** Luis Argerich and community
- **License:** LGPL
- **Website:** https://tiki.org

### Evolution and Scope

Unlike most wiki engines that focus solely on collaborative editing, TikiWiki evolved into a **full-featured CMS** with:

- **Wiki** (collaborative content editing)
- **CMS** (content management system)
- **Groupware** (calendars, tasks, collaboration tools)
- **Forums** (discussion boards)
- **Blogs** (multi-user blogging)
- **Articles** (news/magazine publishing)
- **Trackers** (databases and forms)
- **File galleries** (document management)
- **And 40+ other features**

This makes Tiki one of the **most feature-rich** open-source applications, sometimes described as "the Swiss Army knife of CMSes."

### Design Philosophy

TikiWiki's markup reflects its **comprehensive** approach:

1. **Feature-complete:** Syntax for every wiki need
2. **Unambiguous:** Clear delimiters, minimal parsing edge cases
3. **Compatible:** Influenced by but distinct from other wikis
4. **Extensible:** Plugin/module integration
5. **Practical:** Syntax evolved from real-world usage

---

## TikiWiki Markup Syntax Reference

### Basic Formatting

#### Bold
```
__bold text__
```
**Renders as:** **bold text**

#### Italic
```
''italic text''
```
**Renders as:** *italic text*

#### Underline
```
===underlined text===
```
**Renders as:** <u>underlined text</u>

#### Monospace/Teletype
```
-+monospace text+-
```
**Renders as:** `monospace text`

#### Center Text
```
::centered text::
```
**Renders as:** <center>centered text</center>

#### Colored Text
```
~~red:red text~~
~~#FF0000:hex color~~
```
**Format:** `~~color:text~~` where color is a name or hex code

### Headings

TikiWiki uses exclamation marks for headings:

```
! Heading Level 1
!! Heading Level 2
!!! Heading Level 3
```

**Format:** `!{1,6} Heading Text` - exclamation marks followed by space

**Maximum:** 6 heading levels supported

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

#### Mixed Lists
```
* Bullet item
*# Numbered sub-item under bullet
*# Another numbered sub-item
* Another bullet
```

**Format:** Repeat `*` or `#` to increase nesting level

### Links

#### Inline URLs
```
http://example.com
https://example.com/page
ftp://ftp.example.com
mailto:user@example.com
```

**Auto-detected schemes:** `http://`, `https://`, `ftp://`, `mailto:`, and others

#### External Links with Description
```
[http://example.com|Link description]
```
**Format:** `[URL|description text]` - pipe separator

#### Wiki Links

**Simple wiki page links:**
```
((WikiPageName))
```

**Wiki links with display text:**
```
((WikiPageName|Display Text))
```

**Wiki links with anchor:**
```
((WikiPageName#anchor))
((WikiPageName#anchor|Display Text))
```

**Format:** Double parentheses `((page))` or `((page|text))`

#### Interwiki Links
```
((Wikipedia:Article_Name))
((OtherWiki:PageName|Description))
```

### Tables

TikiWiki tables use double pipes with specific syntax:

```
||Header 1||Header 2||Header 3
Row 1 Col 1|Row 1 Col 2|Row 1 Col 3
Row 2 Col 1|Row 2 Col 2|Row 2 Col 3||
```

**Key features:**
- **First row** starting with `||` is treated as headers
- **Regular rows** use single `|` between cells
- **Optional closing** `||` at end of rows
- **Alignment:** Cells auto-aligned based on content

**Table with all features:**
```
||~Header (centered)||<Left-aligned||>Right-aligned
Normal|Left|Right||
```

### Code and Preformatted Text

#### Code Blocks
```
{CODE()}
function example() {
    return true;
}
{CODE}
```

**With language highlighting:**
```
{CODE(language=>php)}
<?php
echo "Hello World";
?>
{CODE}
```

**With line numbers:**
```
{CODE(ln=>1)}
Line 1
Line 2
{CODE}
```

### Blockquotes

```
> This is a quoted line
> Another quoted line
>> Nested quote (two angle brackets)
```

**Format:** Lines starting with `>` - more angle brackets for deeper nesting

### Horizontal Rules

```
----
```

Four hyphens create a horizontal rule.

### Special Wiki Elements

#### Anchors (Named Locations)
```
{ANAME()}anchorname{ANAME}

Link to: ((PageName#anchorname|Jump to anchor))
```

#### Images

**Simple image:**
```
{img src="image.jpg"}
{img src="http://example.com/image.png"}
```

**With attributes:**
```
{img src="image.jpg" alt="Alt text" width="300" height="200" align="left"}
```

**From file gallery:**
```
{img fileId="123"}
```

#### Plugins and Modules

TikiWiki uses `{PLUGIN}...{PLUGIN}` syntax extensively:

**Box container:**
```
{BOX(title="Box Title")}
Content inside a box
{BOX}
```

**Fancy table:**
```
{FANCYTABLE()}
||Header 1||Header 2
Cell 1|Cell 2||
{FANCYTABLE}
```

**Dynamic content:**
```
{DYNAMIC()}
Content that updates dynamically
{DYNAMIC}
```

**Lists with custom formatting:**
```
{LIST()}
* Item 1
* Item 2
{LIST}
```

### Advanced Features

#### Subscript and Superscript
```
H{SUB()}2{SUB}O         → H₂O
E=mc{SUP()}2{SUP}       → E=mc²
```

#### Strikethrough
```
--strikethrough text--
```

#### Non-Breaking Space
```
~hs~     (single hard space)
~hs~hs~  (multiple hard spaces)
```

#### Table of Contents
```
{maketoc}
```

Auto-generates table of contents from headings.

#### Definition Lists
```
; Term 1
: Definition for term 1
; Term 2
: Definition for term 2
```

**Format:** Semicolon for term, colon for definition

---

## Plugins System

TikiWiki's extensive **plugin system** uses consistent syntax:

### Plugin Format
```
{PLUGINNAME(param1="value1" param2="value2")}
Content (if applicable)
{PLUGINNAME}
```

### Common Plugins

**Include pages:**
```
{INCLUDE(page="OtherPage")}
```

**Show/hide content:**
```
{TOGGLE(title="Click to expand")}
Hidden content here
{TOGGLE}
```

**Split content into tabs:**
```
{TABS()}
{TAB(name="Tab 1")}Content 1{TAB}
{TAB(name="Tab 2")}Content 2{TAB}
{TABS}
```

**Embed YouTube:**
```
{YOUTUBE(id="VIDEO_ID")}
```

**RSS feed:**
```
{RSS(url="http://example.com/feed")}
```

**Draw diagrams:**
```
{DRAW(name="diagram1")}
```

**Countdown timer:**
```
{COUNTDOWN(date="2026-12-31 23:59:59")}
```

---

## Parser Implementation Notes

### Case Sensitivity

TikiWiki plugins are **case-insensitive**:
- `{CODE}` = `{code}` = `{Code}`
- Parser normalizes to uppercase internally

### Escaping Wiki Markup

To display literal wiki markup:

**Tilde escape:**
```
~np~((NotAWikiLink))~/np~
```

**No-parse blocks:**
```
{CODE()}
Wiki markup __not__ processed here
{CODE}
```

### Nesting Rules

- **Lists can nest** within each other
- **Plugins can nest** within plugins (with care)
- **Tables cannot nest** within table cells
- **Code blocks** prevent inner markup processing

---

## Comparison to Other Dialects

| Feature | TikiWiki | MediaWiki | Default | coWiki |
|---------|----------|-----------|---------|--------|
| **Bold** | `__text__` | `'''text'''` | `'''text'''` | `*text*` |
| **Italic** | `''text''` | `''text''` | `''text''` | `/text/` |
| **Heading L1** | `! Text` | `= Text =` | `+ Text` | `+ Text` |
| **Unordered List** | `* Item` | `* Item` | `* Item` | `* Item` |
| **Ordered List** | `# Item` | `# Item` | `# Item` | `# Item` |
| **External Links** | `[url\|text]` | `[url text]` | `[url text]` | `((url)(text))` |
| **Wiki Links** | `((Page))` or `((Page\|text))` | `[[Page]]` | `WikiName` or `[Page]` | `WikiName` |
| **Tables** | `\|\|header` then `cell\|cell\|\|` | `{\| ... \|}` | `\|\| cell \|\| cell \|\|` | `<table>...</table>` |
| **Code** | `{CODE}...{CODE}` | `<code>...</code>` | `<code>...</code>` | `<code>...</code>` |

**Key Distinctions:**

1. **Double underscore bold** (`__text__`) is unique to Tiki
2. **Exclamation mark headings** (`! Heading`) stand out
3. **Double parentheses wiki links** (`((Page))`) differ from MediaWiki's `[[Page]]`
4. **Extensive plugin syntax** (`{PLUGIN}...{PLUGIN}`) is Tiki's signature feature
5. **Pipe separator in links** (`[url|text]`) vs. MediaWiki's space

---

## Historical Development

### Early Years (2002-2005)

- **2002:** Luis Argerich starts Tiki project
- **2003:** Rapidly adds wiki, CMS, and groupware features
- **2004:** Plugin system matures
- **2005:** Becomes one of most feature-rich FOSS CMSes

### Maturation (2006-2015)

- **2007:** Tiki 2.0 - major refactoring
- **2010:** Tiki 6.0 - jQuery integration
- **2012:** Tiki 9.0 - responsive themes
- **2015:** Tiki 14.0 - modern PHP practices

### Modern Era (2016-Present)

- **2018:** Tiki 18.0 - composer support
- **2020:** Tiki 21.0 - improved UX
- **2023:** Tiki 26.0 - accessibility improvements
- **2024-2026:** Tiki 27-28 - continued active development

**Status:** Still actively developed with 3-4 releases per year

---

## Use Cases

TikiWiki dialect is ideal for:

1. **Corporate intranets** - comprehensive groupware
2. **Educational institutions** - wiki + course management
3. **Knowledge bases** - structured documentation
4. **Community portals** - forums + wiki + blogs
5. **Project management** - trackers + wiki + calendars

**Strengths:**
- **All-in-one solution** - no need for separate wiki/CMS/forum
- **Mature** - 20+ years of development
- **Active community** - ongoing support and plugins
- **Flexible** - extensive configuration options

**Weaknesses:**
- **Learning curve** - many features to learn
- **Plugin syntax** - not as intuitive as pure markdown
- **Performance** - feature-richness can impact speed
- **Unique syntax** - not portable to other platforms

---

## Migration and Compatibility

### From MediaWiki to TikiWiki

**Common patterns:**

| MediaWiki | TikiWiki |
|-----------|----------|
| `'''bold'''` | `__bold__` |
| `[[Link]]` | `((Link))` |
| `= Heading =` | `! Heading` |
| `[url text]` | `[url\|text]` |

**Tools:** Tiki includes MediaWiki import functionality

### From TikiWiki to Other Formats

**Text_Wiki enables:**
- Parse TikiWiki markup
- Render to XHTML, LaTeX, Plain Text
- Enables content reuse and archival

---

## Authoritative Sources

- **Official Site:** https://tiki.org
- **Documentation:** https://doc.tiki.org
- **Syntax Reference:** https://doc.tiki.org/Wiki-Syntax-Text
- **Plugin Reference:** https://doc.tiki.org/PluginList
- **PEAR Package:** https://pear.php.net/package/Text_Wiki_Tiki/

---

## Summary

TikiWiki represents the **comprehensive approach** to wiki markup:

- **Full-featured syntax** supporting every wiki need
- **Plugin architecture** for extensibility
- **Clear delimiters** reducing ambiguity
- **Mature ecosystem** with 20+ years of evolution
- **Unique identity** - not trying to clone other wikis

**Key Contribution:** Demonstrated that wikis could be **full CMSes**, not just collaborative editing tools.

**Legacy:** Influenced thinking about wiki engines as **application platforms** rather than simple markup converters.

The TikiWiki dialect in Text_Wiki preserves this syntax and enables content created in Tiki to be transformed into other formats.

## TODOs:

Double-check if TikiWiki has evolved any additional syntax we need to port into Text_Wiki
