<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\BBCode;

use Horde\Text\Wiki\Token;
use Horde\Text\Wiki\TokenType;
use Horde\Text\Wiki\Tokenizer;

/**
 * BBCode tokenizer
 *
 * Converts BBCode text into a stream of tokens.
 * Handles [tag], [tag=value], [tag attr=value], and [/tag] patterns.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class BBCodeTokenizer implements Tokenizer
{
    /**
     * Tokenize BBCode text
     *
     * Returns a generator that yields tokens:
     * - OPEN_TAG: [tag] or [tag=value] or [tag attr=value]
     * - CLOSE_TAG: [/tag]
     * - TEXT: Plain text between tags
     * - NEWLINE: Line breaks (for block-level handling)
     *
     * @param string $text BBCode input text
     *
     * @return iterable<Token> Stream of tokens
     */
    public function tokenize(string $text): iterable
    {
        $length = strlen($text);
        $position = 0;

        while ($position < $length) {
            // Look for opening bracket [
            $bracketPos = strpos($text, '[', $position);

            // No more tags - yield remaining text
            if ($bracketPos === false) {
                if ($position < $length) {
                    $remaining = substr($text, $position);
                    foreach ($this->tokenizeText($remaining, $position) as $token) {
                        yield $token;
                    }
                }
                break;
            }

            // Yield text before bracket
            if ($bracketPos > $position) {
                $textBefore = substr($text, $position, $bracketPos - $position);
                foreach ($this->tokenizeText($textBefore, $position) as $token) {
                    yield $token;
                }
            }

            // Try to parse tag starting at bracket
            $tagResult = $this->parseTag($text, $bracketPos);

            if ($tagResult === null) {
                // Not a valid tag - treat [ as literal text
                yield new Token(TokenType::TEXT, '[', $bracketPos);
                $position = $bracketPos + 1;
            } else {
                // Valid tag found
                yield $tagResult['token'];
                $position = $tagResult['end'];
            }
        }
    }

    /**
     * Tokenize plain text (split on newlines)
     *
     * Returns array of TEXT tokens and NEWLINE tokens.
     * Changed from generator to array to avoid key collisions in iterator_to_array().
     *
     * @param string $text     Text content
     * @param int    $position Starting position
     *
     * @return array<Token> Array of text and newline tokens
     */
    protected function tokenizeText(string $text, int $position): array
    {
        $tokens = [];
        $lines = explode("\n", $text);
        $currentPos = $position;

        foreach ($lines as $i => $line) {
            if ($line !== '') {
                $tokens[] = new Token(TokenType::TEXT, $line, $currentPos);
                $currentPos += strlen($line);
            }

            // Add newline token (except after last line)
            if ($i < count($lines) - 1) {
                $tokens[] = new Token(TokenType::NEWLINE, "\n", $currentPos);
                $currentPos += 1;
            }
        }

        return $tokens;
    }

    /**
     * Parse a tag starting at given position
     *
     * Returns array with 'token' and 'end' position, or null if invalid.
     *
     * Handles:
     * - [tag]
     * - [tag=value]
     * - [tag="quoted value"]
     * - [tag attr=value attr2=value2]
     * - [/tag]
     *
     * @param string $text     Input text
     * @param int    $startPos Position of opening [
     *
     * @return array{token: Token, end: int}|null Parsed tag or null
     */
    protected function parseTag(string $text, int $startPos): ?array
    {
        // Find closing bracket ]
        $closePos = strpos($text, ']', $startPos + 1);
        if ($closePos === false) {
            return null; // No closing bracket
        }

        // Extract tag content between [ and ]
        $tagContent = substr($text, $startPos + 1, $closePos - $startPos - 1);
        $tagContent = trim($tagContent);

        if ($tagContent === '') {
            return null; // Empty tag
        }

        // Check for closing tag [/tag]
        if ($tagContent[0] === '/') {
            $tagName = substr($tagContent, 1);
            $tagName = strtolower(trim($tagName));

            if ($tagName === '') {
                return null; // Invalid [/]
            }

            return [
                'token' => new Token(
                    TokenType::CLOSE_TAG,
                    $tagName,
                    $startPos,
                    [],
                    $tagContent,
                ),
                'end' => $closePos + 1,
            ];
        }

        // Parse opening tag
        $parsed = $this->parseOpeningTag($tagContent);
        if ($parsed === null) {
            return null;
        }

        return [
            'token' => new Token(
                TokenType::OPEN_TAG,
                $parsed['name'],
                $startPos,
                $parsed['attributes'],
                $tagContent,
            ),
            'end' => $closePos + 1,
        ];
    }

    /**
     * Parse opening tag content
     *
     * Handles:
     * - tag
     * - tag=value
     * - tag="quoted value"
     * - tag attr=value attr2="value 2"
     *
     * Returns ['name' => 'tag', 'attributes' => [...]] or null.
     *
     * @param string $content Tag content (between [ and ])
     *
     * @return array{name: string, attributes: array}|null Parsed tag or null
     */
    protected function parseOpeningTag(string $content): ?array
    {
        // Extract tag name (first word)
        $spacePos = strcspn($content, " =");
        $tagName = substr($content, 0, $spacePos);
        $tagName = strtolower(trim($tagName));

        if ($tagName === '') {
            return null;
        }

        $attributes = [];
        $remainder = substr($content, $spacePos);

        // Handle [tag=value] shorthand (single attribute)
        if (str_starts_with(trim($remainder), '=')) {
            $value = trim(substr($remainder, 1));
            $value = $this->unquoteValue($value);

            // Map to attribute name based on tag
            $attrName = $this->getDefaultAttributeName($tagName);
            $attributes[$attrName] = $value;

            return [
                'name' => $tagName,
                'attributes' => $attributes,
            ];
        }

        // Parse multiple attributes [tag attr1=value1 attr2=value2]
        if (trim($remainder) !== '') {
            $attributes = $this->parseAttributes(trim($remainder));
        }

        return [
            'name' => $tagName,
            'attributes' => $attributes,
        ];
    }

    /**
     * Parse multiple attributes
     *
     * Handles: attr1=value1 attr2="value 2" attr3='value 3'
     *
     * @param string $attrString Attribute string
     *
     * @return array Parsed attributes
     */
    protected function parseAttributes(string $attrString): array
    {
        $attributes = [];
        $length = strlen($attrString);
        $position = 0;

        while ($position < $length) {
            // Skip whitespace
            while ($position < $length && ctype_space($attrString[$position])) {
                $position++;
            }

            if ($position >= $length) {
                break;
            }

            // Extract attribute name
            $nameEnd = $position;
            while ($nameEnd < $length
                   && $attrString[$nameEnd] !== '='
                   && !ctype_space($attrString[$nameEnd])) {
                $nameEnd++;
            }

            $attrName = substr($attrString, $position, $nameEnd - $position);
            $position = $nameEnd;

            // Skip whitespace and =
            while ($position < $length
                   && (ctype_space($attrString[$position]) || $attrString[$position] === '=')) {
                $position++;
            }

            // Extract value
            $value = '';
            if ($position < $length) {
                $quote = $attrString[$position];

                if ($quote === '"' || $quote === "'") {
                    // Quoted value
                    $position++; // Skip opening quote
                    $valueStart = $position;
                    while ($position < $length && $attrString[$position] !== $quote) {
                        $position++;
                    }
                    $value = substr($attrString, $valueStart, $position - $valueStart);
                    if ($position < $length) {
                        $position++; // Skip closing quote
                    }
                } else {
                    // Unquoted value (until space)
                    $valueStart = $position;
                    while ($position < $length && !ctype_space($attrString[$position])) {
                        $position++;
                    }
                    $value = substr($attrString, $valueStart, $position - $valueStart);
                }
            }

            if ($attrName !== '') {
                $attributes[strtolower($attrName)] = $value;
            }
        }

        return $attributes;
    }

    /**
     * Remove quotes from value if present
     *
     * @param string $value Value string
     *
     * @return string Unquoted value
     */
    protected function unquoteValue(string $value): string
    {
        $value = trim($value);

        if (strlen($value) < 2) {
            return $value;
        }

        $first = $value[0];
        $last = $value[strlen($value) - 1];

        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Get default attribute name for [tag=value] shorthand
     *
     * Maps tag names to their default attribute:
     * - [url=...] → href
     * - [email=...] → email
     * - [color=...] → color
     * - [font=...] → font
     * - [size=...] → size
     * - [quote=...] → author
     * - [list=...] → type
     * - [img=...] → alt (for [img alt="..."])
     * - [code=...] → language
     *
     * @param string $tagName Tag name
     *
     * @return string Attribute name
     */
    protected function getDefaultAttributeName(string $tagName): string
    {
        return match ($tagName) {
            'url' => 'href',
            'email' => 'email',
            'color' => 'color',
            'font' => 'font',
            'size' => 'size',
            'quote' => 'author',
            'list' => 'type',
            'img' => 'alt',
            'code' => 'language',
            default => 'value',
        };
    }
}
