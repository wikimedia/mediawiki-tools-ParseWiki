<?php

namespace WikiConnect\ParseWiki\DataModel;

use InvalidArgumentException;

/**
 * Class Cell
 *
 * Represents one cell in a table (header or data), with content, attributes,
 * and optional nested table.
 */
class Cell
{
    /** @var string Content text (may include wiki markup, nested table, etc.) */
    private string $content;

    /**
     * @var array<string,string> Attributes (e.g. style, colspan, rowspan, class)
     */
    private array $attrs = [];

    /** @var Table|null If this cell includes a nested table, stored here */
    private ?Table $nestedTable = null;

    /** @var bool Whether to escape HTML in content */
    private bool $escapeHtml = true;

    /** @var int Number of rows this cell spans */
    private int $rowSpan = 1;

    /** @var int Number of columns this cell spans */
    private int $colSpan = 1;

    /** @var string Cell alignment (left, center, right, justify) */
    private string $align = '';

    /** @var string Cell scope (col, row, colgroup, rowgroup) for headers */
    private string $scope = '';

    /**
     * @param string $content
     * @param array<string,string> $attrs
     * @param bool $escapeHtml
     */
    public function __construct(string $content = '', array $attrs = [], bool $escapeHtml = true)
    {
        $this->escapeHtml = $escapeHtml;
        $this->setContent($content);
        $this->setAttributes($attrs);
    }

    /**
     * Get the cell content.
     * Does not include the nested table if present.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->escapeHtml ? htmlspecialchars($this->content, ENT_QUOTES) : $this->content;
    }

    /**
     * Get the raw (unescaped) cell content.
     *
     * @return string
     */
    public function getRawContent(): string
    {
        return $this->content;
    }

    /**
     * Set the cell content.
     *
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        
        $lines = explode("\n", $content);
        $lines = array_map('trim', $lines);
        $this->content = implode("\n", $lines);
    }

    /**
     * Set whether to escape HTML in content.
     *
     * @param bool $escape
     */
    public function setEscapeHtml(bool $escape): void
    {
        $this->escapeHtml = $escape;
    }

    /**
     * Get whether HTML escaping is enabled.
     *
     * @return bool
     */
    public function getEscapeHtml(): bool
    {
        return $this->escapeHtml;
    }

    /**
     * Set row span for this cell.
     *
     * @param int $span Number of rows to span
     * @throws InvalidArgumentException if span is less than 1
     */
    public function setRowSpan(int $span): void
    {
        if ($span < 1) {
            throw new InvalidArgumentException('Row span must be at least 1');
        }
        $this->rowSpan = $span;
        if ($span > 1) {
            $this->attrs['rowspan'] = (string)$span;
        }
    }

    /**
     * Get row span value.
     *
     * @return int
     */
    public function getRowSpan(): int
    {
        return $this->rowSpan;
    }

    /**
     * Set column span for this cell.
     *
     * @param int $span Number of columns to span
     * @throws InvalidArgumentException if span is less than 1
     */
    public function setColSpan(int $span): void
    {
        if ($span < 1) {
            throw new InvalidArgumentException('Column span must be at least 1');
        }
        $this->colSpan = $span;
        if ($span > 1) {
            $this->attrs['colspan'] = (string)$span;
        }
    }

    /**
     * Get column span value.
     *
     * @return int
     */
    public function getColSpan(): int
    {
        return $this->colSpan;
    }

    /**
     * Set cell alignment.
     *
     * @param string $align One of: left, center, right, justify
     * @throws InvalidArgumentException if alignment is invalid
     */
    public function setAlign(string $align): void
    {
        $valid = ['left', 'center', 'right', 'justify', ''];
        if (!in_array($align, $valid)) {
            throw new InvalidArgumentException('Invalid alignment value');
        }
        $this->align = $align;
        if ($align !== '') {
            $this->attrs['align'] = $align;
        }
    }

    /**
     * Get cell alignment.
     *
     * @return string
     */
    public function getAlign(): string
    {
        if (!empty($this->align)) {
            return $this->align;
        }
        
        if (isset($this->attrs['align'])) {
            return $this->attrs['align'];
        }
        
        if (isset($this->attrs['style'])) {
            if (preg_match('/text-align\s*:\s*(left|center|right|justify)/', $this->attrs['style'], $matches)) {
                return $matches[1];
            }
        }
        
        return '';
    }

    /**
     * Set header cell scope.
     *
     * @param string $scope One of: col, row, colgroup, rowgroup
     * @throws InvalidArgumentException if scope is invalid
     */
    public function setScope(string $scope): void
    {
        $valid = ['col', 'row', 'colgroup', 'rowgroup', ''];
        if (!in_array($scope, $valid)) {
            throw new InvalidArgumentException('Invalid scope value');
        }
        $this->scope = $scope;
        if ($scope !== '') {
            $this->attrs['scope'] = $scope;
        }
    }

    /**
     * Get header cell scope.
     *
     * @return string
     */
    public function getScope(): string
    {
        // Check if scope is set directly
        if (!empty($this->scope)) {
            return $this->scope;
        }
        
        // Check if scope is in attributes
        if (isset($this->attrs['scope'])) {
            return $this->attrs['scope'];
        }
        
        return '';
    }

    /**
     * Set the attributes.
     *
     * @param array<string,string> $attrs
     * @throws InvalidArgumentException if attribute name is invalid
     */
    public function setAttributes(array $attrs): void
    {
        // Validate attribute names
        foreach ($attrs as $name => $value) {
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9-_]*$/', $name)) {
                throw new InvalidArgumentException("Invalid attribute name: $name");
            }
        }

        // Process special attributes
        if (isset($attrs['rowspan'])) {
            $this->setRowSpan((int)$attrs['rowspan']);
        }
        if (isset($attrs['colspan'])) {
            $this->setColSpan((int)$attrs['colspan']);
        }
        if (isset($attrs['align'])) {
            $this->setAlign($attrs['align']);
        }
        if (isset($attrs['scope'])) {
            $this->setScope($attrs['scope']);
        }

        // Convert styling shortcuts to individual style properties
        $styleProps = [];
        
        // First get existing styles as individual properties
        if (isset($this->attrs['style'])) {
            foreach (explode(';', $this->attrs['style']) as $style) {
                if (trim($style) !== '') {
                    $styleProps[] = trim($style);
                }
            }
        }

        // Add new style properties from shortcuts
        foreach ($attrs as $key => $value) {
            if (in_array($key, ['width', 'height', 'bgcolor', 'background', 'color'])) {
                // Convert bgcolor to background-color for proper CSS
                if ($key === 'bgcolor') {
                    $styleProps[] = 'background-color: ' . $value;
                } else {
                    $styleProps[] = $key . ': ' . $value;
                }
                // Keep the original attribute for HTML compatibility, but remove bgcolor since it's converted
                if ($key === 'bgcolor') {
                    unset($attrs[$key]);
                }
            }
        }

        // Add new style properties from style attribute
        if (isset($attrs['style'])) {
            foreach (explode(';', $attrs['style']) as $style) {
                if (trim($style) !== '') {
                    $styleProps[] = trim($style);
                }
            }
            unset($attrs['style']);
        }

        // Remove duplicates by property name (keep last occurrence)
        $uniqueStyles = [];
        foreach ($styleProps as $style) {
            if (preg_match('/^([^:]+):/', $style, $m)) {
                $prop = trim($m[1]);
                $uniqueStyles[$prop] = $style;
            }
        }

        // Set combined style attribute
        if (!empty($uniqueStyles)) {
            $attrs['style'] = implode(';', array_values($uniqueStyles)) . ';';
        }

        // Handle style-based alignment
        if (isset($attrs['style'])) {
            if (preg_match('/text-align\s*:\s*(left|center|right|justify)/', $attrs['style'], $matches)) {
                $this->setAlign($matches[1]);
                // Don't remove the alignment from style as it may be intentional
            }
        }



        // Support sort keys
        if (isset($attrs['data-sort-type']) && in_array($attrs['data-sort-type'], ['number', 'text', 'date', 'currency'])) {
            $attrs['data-sort-type'] = $attrs['data-sort-type'];
        }
        if (isset($attrs['data-sort-value'])) {
            $attrs['data-sort-value'] = $attrs['data-sort-value'];
        }

        // Merge with existing attributes, letting new ones override
        $this->attrs = array_merge($this->attrs, $attrs);
    }

    /**
     * Get the cell attributes.
     *
     * @return array<string,string>
     */
    public function getAttributes(): array
    {
        return $this->attrs;
    }

    /**
     * Set a nested table for this cell.
     *
     * @param Table|null $table
     */
    public function setNestedTable(?Table $table): void
    {
        $this->nestedTable = $table;
    }

    /**
     * Get the nested table (if any).
     *
     * @return Table|null
     */
    public function getNestedTable(): ?Table
    {
        return $this->nestedTable;
    }

    /**
     * Convert to string, including any nested table.
     *
     * @return string
     */
    public function __toString(): string
    {
        $result = '';
        
        // Add attributes if present
        if (!empty($this->attrs)) {
            $attrStrings = [];
            foreach ($this->attrs as $k => $v) {
                $attrStrings[] = "{$k}=\"" . htmlspecialchars($v, ENT_QUOTES) . "\"";
            }
            $result .= implode(' ', $attrStrings) . ' | ';
        }
        
        // Add content
        $result .= $this->getRawContent();
        
        // Add nested table if present
        if ($this->nestedTable !== null) {
            $result .= ($this->content ? ' ' : '') . $this->nestedTable->toString();
        }
        
        return $result;
    }
}
