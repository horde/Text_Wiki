<?php

declare(strict_types=1);

/**
 * Copyright 2025-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Wiki\Commonmark;

/**
 * Stores link reference definitions collected during block parsing
 *
 * Link reference definitions have the form:
 *   [label]: destination "title"
 *
 * Labels are case-insensitive and normalized (collapsed whitespace).
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Wiki
 */
class LinkReferenceMap
{
    /** @var array<string, array{destination: string, title: string}> */
    private array $references = [];

    /**
     * Add a link reference definition (first definition wins)
     */
    public function add(string $label, string $destination, string $title = ''): void
    {
        $key = $this->normalizeLabel($label);
        if ($key === '' || isset($this->references[$key])) {
            return;
        }
        $this->references[$key] = [
            'destination' => $destination,
            'title' => $title,
        ];
    }

    /**
     * Look up a reference by label
     *
     * @return array{destination: string, title: string}|null
     */
    public function get(string $label): ?array
    {
        $key = $this->normalizeLabel($label);
        return $this->references[$key] ?? null;
    }

    public function has(string $label): bool
    {
        return isset($this->references[$this->normalizeLabel($label)]);
    }

    /**
     * Normalize a link label per CommonMark spec:
     * - Strip leading/trailing whitespace
     * - Collapse internal whitespace to single space
     * - Case-fold to lowercase
     */
    private function normalizeLabel(string $label): string
    {
        $label = trim($label);
        $label = preg_replace('/\s+/', ' ', $label) ?? $label;
        return mb_convert_case($label, MB_CASE_FOLD, 'UTF-8');
    }
}
