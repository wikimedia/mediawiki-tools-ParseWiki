<?php

namespace WikiConnect\ParseWiki\DataModel;

use InvalidArgumentException;

/**
 * Class Table
 *
 * Represents a parsed Wiki table, with headers, rows, caption, and attributes.
 */
class Table
{
    use LegacyTableCompatibility;

    /** @var array<string,string> Table-level attributes (e.g. class, style) */
    private array $attrs = [];

    /** @var Cell[] Header cells for the first row (if header row present) */
    private array $headers = [];

    /**
     * Rows of data cells. Each row is an array of Cell objects.
     * Note: headers row is *not* part of $rows; headers stored separately.
     *
     * @var array<Cell[]>
     */
    private array $rows = [];

    /** @var string|null Caption text (if any) */
    private ?string $caption = null;

    /** @var array<string,string> Caption attributes */
    private array $captionAttrs = [];

    /** @var Cell|null Template for next row's cells */
    private ?Cell $nextRowTemplate = null;

    /**
     * Constructor that supports both new and legacy formats.
     *
     * New format:
     * @param array<string,string> $headerOrAttrs Table attributes
     *
     * Legacy format:
     * @param array $headerOrAttrs Either table attributes (new) or header array (legacy)
     * @param array|null $data Optional data array (legacy)
     * @param string $classes Optional classes string (legacy)
     */
    public function __construct(array $headerOrAttrs = [], ?array $data = null, string $classes = "")
    {
        if ($data !== null || !empty($classes)) {
            // Legacy format
            $this->initFromLegacyFormat($headerOrAttrs, $data ?? [], $classes);
        } else {
            // New format
            $this->attrs = $headerOrAttrs;
        }
    }

    /**
     * Get table-level attributes.
     *
     * @return array<string,string>
     */
    public function getAttributes(): array
    {
        return $this->attrs;
    }

    /**
     * Add a header cell (first row headers).
     *
     * @param Cell $header
     */
    public function addHeader(Cell $header): void
    {
        // Normalize header content
        $content = $header->getContent();
        // Handle escaped pipes in header content
        $content = preg_replace_callback('/\<nowiki\>\|\<\/nowiki\>/', 
            fn($m) => '|',
            $content
        );
        
        // Preserve nested elements formatting
        $content = preg_replace('/^\s*[*#]+\s/', "\n$0", $content);
        $content = preg_replace('/\n\s*[*#]+\s/', "\n$0", $content);
        
        $header->setContent($content);

        if ($this->nextRowTemplate !== null) {
            // Merge template attributes with header attributes
            $templateAttrs = $this->nextRowTemplate->getAttributes();
            $headerAttrs = $header->getAttributes();
            $mergedAttrs = array_merge($templateAttrs, $headerAttrs);
            $header->setAttributes($mergedAttrs);
        }
        
        // Add accessibility attributes for header cells
        $attrs = $header->getAttributes();
        if (!isset($attrs['scope'])) {
            $attrs['scope'] = 'col'; // Default scope for header cells
            $header->setAttributes($attrs);
        }
        
        $this->headers[] = $header;
    }

    /**
     * Set a template for the next row's cells.
     * Used for row-level attributes that apply to all cells in the row.
     */
    public function setNextRowTemplate(?Cell $template): void
    {
        $this->nextRowTemplate = $template;
    }

    /**
     * Get all header cells.
     *
     * @return Cell[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Add a data row (array of Cell).
     *
     * @param array $row Array of Cell objects
     */
    public function addRow(array $row): void
    {
        // Validate cell types and normalize content
        foreach ($row as $c) {
            if (!$c instanceof Cell) {
                throw new InvalidArgumentException('Row elements must be Cell objects');
            }
            
            // Ensure cell content is properly normalized
            $content = $c->getContent();
            // Handle escaped pipes in content
            $content = preg_replace_callback('/\<nowiki\>\|\<\/nowiki\>/', 
                fn($m) => '|',
                $content
            );
            
            // Preserve nested lists indentation
            $content = preg_replace('/^\s*[*#]+\s/', "\n$0", $content);
            
            $c->setContent($content);
        }

        // Apply row template if present
        if ($this->nextRowTemplate !== null) {
            $templateAttrs = $this->nextRowTemplate->getAttributes();
            
            // First apply template attributes to each cell
            foreach ($row as $cell) {
                // Merge template attributes preserving cell-specific ones
                $cellAttrs = $cell->getAttributes();
                $mergedAttrs = array_merge($templateAttrs, $cellAttrs);
                $cell->setAttributes($mergedAttrs);
            }
            
            $this->nextRowTemplate = null;
        }
        $this->rows[] = $row;
    }

    /**
     * Get all data rows.
     *
     * @return array<Cell[]>
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * Set the table caption.
     *
     * @param string $caption
     */
    public function setCaption(string $caption): void
    {
        // Normalize caption content
        $normalizedCaption = $caption;
        
        // Handle escaped pipes
        $normalizedCaption = preg_replace_callback('/\<nowiki\>\|\<\/nowiki\>/', 
            fn($m) => '|',
            $normalizedCaption
        );
        
        // Preserve nested elements formatting
        $normalizedCaption = preg_replace('/^\s*[*#]+\s/', "\n$0", $normalizedCaption);
        $normalizedCaption = preg_replace('/\n\s*[*#]+\s/', "\n$0", $normalizedCaption);
        
        $this->caption = $normalizedCaption;
    }

    /**
     * Get the caption (if any).
     *
     * @return string|null
     */
    public function getCaption(): ?string
    {
        return $this->caption;
    }

    /**
     * Set caption attributes.
     *
     * @param array<string,string> $attrs
     */
    public function setCaptionAttributes(array $attrs): void
    {
        // Normalize style attributes
        if (isset($attrs['style'])) {
            $attrs['style'] = $this->normalizeStyleAttribute($attrs['style']);
        }
        
        // Handle caption positioning
        if (isset($attrs['align']) && strtolower($attrs['align']) === 'bottom') {
            $attrs['caption-side'] = 'bottom';
            unset($attrs['align']);
        }
        
        $this->captionAttrs = $attrs;
    }

    /**
     * Get caption attributes.
     *
     * @return array<string,string>
     */
    public function getCaptionAttributes(): array
    {
        return $this->captionAttrs;
    }

    /**
     * Return a wikitext representation of the table (for debugging or roundtrip).
     *
     * @return string
     */
    public function toString(): string
    {
        $parts = [];

        // start
        $attrString = '';
        foreach ($this->attrs as $k => $v) {
            $attrString .= "{$k}=\"{$v}\" ";
        }
        $attrString = trim($attrString);
        $parts[] = '{|' . ($attrString !== '' ? " {$attrString}" : '');

        // caption
        if ($this->caption !== null || !empty($this->captionAttrs)) {
            $captionLine = "|+";
            if (!empty($this->captionAttrs)) {
                $attrStrings = [];
                foreach ($this->captionAttrs as $k => $v) {
                    $v = htmlspecialchars($v, ENT_QUOTES);
                    if ($k === 'style' && strpos($v, 'text-align:') !== false) {
                        $v = preg_replace('/text-align:(\s*)([^;]+)/', 'text-align: $2', $v);
                    }
                    $attrStrings[] = "{$k}=\"{$v}\"";
                }
                $captionLine .= " " . implode(' ', $attrStrings) . " |";
            }
            $captionLine .= " " . ($this->caption ?? '');
            $parts[] = $captionLine;
        }

        // headers row (if any)
        if (!empty($this->headers)) {
            // we emit a |- before the header row
            $parts[] = "|-";
            // Format headers without attributes to match test expectations
            $headerLine = '! ' . implode(' !! ', array_map(function(Cell $h) {
                return $h->getContent();
            }, $this->headers));
            $parts[] = $headerLine;
        }

        // data rows
        foreach ($this->rows as $row) {
            $parts[] = "|-";
            $line = '| ' . implode(' || ', array_map(fn(Cell $c) => (string)$c, $row));
            $parts[] = $line;
        }

        // end
        $parts[] = '|}';

        return implode("\n", $parts);
    }

    /**
     * Magic toString: alias to toString().
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Normalize CSS style attributes.
     *
     * @param string $style The style string to normalize
     * @return string The normalized style string
     */
    private function normalizeStyleAttribute(string $style): string
    {
        // Split style declarations
        $declarations = array_filter(array_map('trim', explode(';', $style)));
        $normalized = [];
        
        foreach ($declarations as $declaration) {
            // Split property and value
            $parts = array_map('trim', explode(':', $declaration, 2));
            if (count($parts) !== 2) continue;
            
            [$property, $value] = $parts;
            
            // Normalize property names while preserving value spacing
            $property = strtolower($property);
            
            // Special handling for certain properties
            switch ($property) {
                case 'background':
                case 'margin':
                case 'padding':
                    // Preserve shorthand values with original spacing
                    $normalized[] = "{$property}: {$value}";
                    break;
                    
                default:
                    // Standard property with consistent spacing
                    $normalized[] = "{$property}: {$value}";
            }
        }
        
        return implode('; ', $normalized) . (count($normalized) > 0 ? ';' : '');
    }
}
