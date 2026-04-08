# Integration Guide

## Listing Available Parsers and Renderers

```php
use Horde\Text\Wiki\SimpleFormatCatalog;

$catalog = SimpleFormatCatalog::withDefaults();

// All parser formats the user can write in
$parsers = $catalog->getParserFormats();
// ['bbcode', 'cowiki', 'creole', 'doku', 'mediawiki', 'tiki', 'yawiki']

// All renderer formats
$renderers = $catalog->getRendererFormats();
// ['bbcode', 'cowiki', 'creole', 'docbook', 'doku', 'latex', 'mediawiki', 'plain', 'tiki', 'xhtml', 'yawiki']

// Formats that have both a parser and a renderer (conversion targets)
$convertible = $catalog->getConvertibleFormats();
// ['bbcode', 'cowiki', 'creole', 'doku', 'mediawiki', 'tiki', 'yawiki']

// Check if a specific format is available
if ($catalog->hasParser('mediawiki')) {
    $parser = $catalog->getParser('mediawiki');
}
```

## Deactivating an implemented Tag from a Parser

Build a custom `TagRegistry` that omits the unwanted tag, then inject it
into the parser.

```php
use Horde\Text\Wiki\SimpleTagRegistry;
use Horde\Text\Wiki\Yawiki\YawikiParser;
use Horde\Text\Wiki\Yawiki\Tag\BoldTag;
use Horde\Text\Wiki\Yawiki\Tag\ItalicTag;
use Horde\Text\Wiki\Yawiki\Tag\HeadingTag;
// ... import the tags you want to keep

$tagRegisty = new SimpleTagRegistry();

// Register only the tags you want — omit any you want to deactivate.
// For example, keep bold and italic but drop headings:
$tagRegisty->register(new BoldTag());
$tagRegisty->register(new ItalicTag());
// HeadingTag deliberately omitted — heading markup will be treated as plain text

$parser = new YawikiParser($tagRegisty);
$doc = $parser->parse("+ Heading\n'''bold'''");
// The heading token is unknown to the registry, so GenericStructureBuilder
// treats it as literal text. Bold still works normally.
```

This works with any parser — `BBCodeParser`, `MediawikiParser`, etc. — they
all accept an optional `?TagRegistry` as the first constructor argument
(`MediawikiParser` accepts it as the second, after `$options`).

## Adding a Custom Tag to a Parser and a Renderer

A custom tag needs three things:

1. A `TagDefinition` so the parser's structure builder knows how to
   validate and nest it
2. The tokenizer must already emit it (BBCode's bracket syntax `[tagname]`
   handles any tag name; other tokenizers have hardcoded patterns — if
   your tokenizer doesn't emit the tag, it will never reach the builder)
3. An element handler on the renderer so it produces output

### Step 1: Define the tag

```php
use Horde\Text\Wiki\AbstractTagDefinition;
use Horde\Text\Wiki\TagType;

class WidgetTag extends AbstractTagDefinition
{
    public function __construct()
    {
        parent::__construct('widget', TagType::BLOCK);
    }

    public function validateAttributes(array $attrs): bool
    {
        // Accept any attributes, or add your own validation
        return true;
    }
}
```

### Step 2: Register the tag and create the parser

```php
use Horde\Text\Wiki\SimpleTagRegistry;
use Horde\Text\Wiki\BBCode\BBCodeParser;

// Start from the parser's default registry, then add your tag
// (or build a registry from scratch if you prefer)
$tagRegisty = new SimpleTagRegistry();

// Re-register the built-in tags you want (or call the parser's
// createDefaultRegistry() pattern by extending the parser)
// For BBCode, the simplest approach: create a default parser, then
// create a new registry that includes your custom tag:
$parser = new BBCodeParser(); // uses default registry internally

// Alternative: build a custom registry and inject it
$tagRegisty = new SimpleTagRegistry();
// ... register all default BBCode tags you want ...
$tagRegisty->register(new WidgetTag());

$parser = new BBCodeParser($tagRegisty);

// Now [widget]content[/widget] is a recognized tag in the AST
$doc = $parser->parse('[widget]Live dashboard[/widget]');
```

### Step 3: Register an element handler on the renderer

```php
use Horde\Text\Wiki\Node\ElementNode;
use Horde\Text\Wiki\NodeVisitor;
use Horde\Text\Wiki\Renderer\Xhtml;

$renderer = new Xhtml();

$renderer->setElementHandler('widget', function (ElementNode $node, NodeVisitor $visitor): string {
    $id = $node->getAttribute('id') ?? 'default';
    $children = $visitor->renderChildren($node);
    return '<div class="widget" data-widget-id="' . htmlspecialchars($id) . '">'
        . $children
        . '</div>';
});

$html = $renderer->render($doc);
// <div class="widget" data-widget-id="default">Live dashboard</div>
```

### Wiring it together via the catalog

```php
use Horde\Text\Wiki\SimpleFormatCatalog;
use Horde\Text\Wiki\DefaultEngine;

$catalog = SimpleFormatCatalog::withDefaults();

// Replace the default Xhtml renderer with your customized one
$catalog->registerRenderer($renderer);

// Replace the default parser if you added custom tags to its registry
$catalog->registerParser($parser);

$engine = new DefaultEngine($catalog);
$html = $engine->transform('[widget]Live dashboard[/widget]', 'Xhtml');
```

## Overloading an Existing Tag in a Renderer

Use `setElementHandler()` to replace how a built-in tag renders. The
injected handler takes priority over the renderer's built-in method.

```php
use Horde\Text\Wiki\Node\ElementNode;
use Horde\Text\Wiki\NodeVisitor;
use Horde\Text\Wiki\Renderer\Xhtml;

$renderer = new Xhtml();

// Override the built-in URL rendering to mark missing wiki pages
$renderer->setElementHandler('url', function (ElementNode $node, NodeVisitor $visitor): string {
    $href = $node->getAttribute('href') ?? $node->getAttribute('attr') ?? '#';
    $text = $visitor->renderChildren($node);

    // Your application logic to check if the wiki page exists
    $pageExists = wikiPageExists($href);

    $class = $pageExists ? 'wiki-link' : 'wiki-link missing';
    return '<a href="' . htmlspecialchars($href) . '" class="' . $class . '">'
        . ($text ?: htmlspecialchars($href))
        . '</a>';
});

// Override heading rendering to collect a TOC
$tocEntries = [];
$renderer->setElementHandler('heading', function (ElementNode $node, NodeVisitor $visitor) use (&$tocEntries): string {
    $level = (int) ($node->getAttribute('level') ?? 2);
    $text = $visitor->renderChildren($node);
    $id = 'heading-' . count($tocEntries);

    $tocEntries[] = ['level' => $level, 'text' => strip_tags($text), 'id' => $id];

    return '<h' . $level . ' id="' . $id . '">' . $text . '</h' . $level . '>';
});
```

The handler signature is always
`callable(ElementNode $node, NodeVisitor $renderer): string`.
The `$renderer` argument lets you call `$renderer->renderChildren($node)` to
render the tag's children with the renderer's normal pipeline.

Handler lookup is case-insensitive — `setElementHandler('URL', ...)` and
`setElementHandler('url', ...)` are equivalent.

Every renderer supports `setElementHandler()`: Xhtml, Plain, Latex,
Docbook, Mediawiki, Yawiki, Doku, Creole, Cowiki, Tiki, and BBCode.
