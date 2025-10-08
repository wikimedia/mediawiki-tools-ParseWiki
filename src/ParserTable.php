<?php

declare(strict_types=1);

namespace WikiConnect\ParseWiki;

use WikiConnect\ParseWiki\DataModel\Table;
use WikiConnect\ParseWiki\DataModel\Cell;

/**
 * Table Parser
 * 
 * Parses table markup and converts it to a structured Table object.
 * Supports table attributes, captions, headers, rows, and nested tables.
 */
class ParserTable
{
    private string $tableText;
    private array $nestedTablePlaceholders = [];
    private static int $nestedTableCounter = 0;

    public function __construct(string $tableText = '')
    {
        $this->tableText = trim($tableText);
        $this->tableText = $this->stripHtmlWrappers($this->tableText);
    }

    /**
     * Strip HTML wrapper elements to extract pure WikiText table syntax
     */
    private function stripHtmlWrappers(string $input): string
    {
        $input = trim($input);
        
        $input = preg_replace('/<div[^>]*>\s*(\{\|.*?\|\})\s*<\/div>/s', '$1', $input);
        $input = preg_replace('/<div>\s*(\{\|.*?\|\})\s*<\/div>/s', '$1', $input);
        $input = preg_replace('/<(?:span|section|article)[^>]*>\s*(\{\|.*?\|\})\s*<\/(?:span|section|article)>/s', '$1', $input);
        
        while (preg_match('/<[^>]+>\s*(\{\|.*?\|\})\s*<\/[^>]+>/s', $input, $matches)) {
            $input = preg_replace('/<[^>]+>\s*(\{\|.*?\|\})\s*<\/[^>]+>/s', '$1', $input);
        }
        
        $input = preg_replace('/^[^{]*(\{\|.*?\|\})[^}]*$/s', '$1', $input);
        
        return trim($input);
    }

    public function parse(): ?Table
    {
        if (!preg_match('/^\{\|/', $this->tableText)) {
            return null;
        }

        $tableText = $this->preprocessSingleLineTable($this->tableText);
        $lines = $this->preprocessNestedTables($tableText);
        
        $table = null;
        $currentRow = [];
        $inCaption = false;
        $captionText = '';
        $afterFirstRowSeparator = false;
        $accumulatedCellContent = [];
        $inMultiLineCell = false;
        $currentRowIndex = 0;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $originalLine = $line;
            $line = trim($line);
            
            if (preg_match('/^\{\|(.*)$/', $line, $m)) {
                $tableAttrs = $this->parseSimpleAttributes(trim($m[1]));
                $table = new Table($tableAttrs);
                continue;
            }

            if (!$table) {
                continue;
            }

            if ($line === '|}') {
                if ($inMultiLineCell && !empty($accumulatedCellContent)) {
                    $this->finalizeMultiLineCells($accumulatedCellContent, $currentRow, false);
                    $accumulatedCellContent = [];
                    $inMultiLineCell = false;
                }
                
                if (!empty($currentRow)) {
                    $table->addRow($currentRow);
                    $currentRow = [];
                }
                break;
            }

            if (preg_match('/^\|\+(.*)$/', $line, $m)) {
                if ($inMultiLineCell && !empty($accumulatedCellContent)) {
                    $this->finalizeMultiLineCells($accumulatedCellContent, $currentRow, false);
                    $accumulatedCellContent = [];
                    $inMultiLineCell = false;
                }
                
                $captionContent = trim($m[1]);
                $this->parseCaptionWithAttributes($captionContent, $table);
                continue;
            }

            if (preg_match('/^\|-(.*)$/', $line, $m)) {
                if ($inMultiLineCell && !empty($accumulatedCellContent)) {
                    $this->finalizeMultiLineCells($accumulatedCellContent, $currentRow, false);
                    $accumulatedCellContent = [];
                    $inMultiLineCell = false;
                }
                
                if (!empty($currentRow)) {
                    $table->addRow($currentRow);
                    $currentRow = [];
                    $currentRowIndex++;
                }
                $afterFirstRowSeparator = true;
                continue;
            }

            if (preg_match('/^!(.*)$/', $line, $m)) {
                if ($inMultiLineCell && !empty($accumulatedCellContent)) {
                    $this->finalizeMultiLineCells($accumulatedCellContent, $currentRow, false);
                    $accumulatedCellContent = [];
                    $inMultiLineCell = false;
                }
                
                if (preg_match('/^\s*scope\s*=\s*["\']?row["\']?/', $m[1])) {
                    $this->parseMultiLineCells($m[1], $accumulatedCellContent, true);
                    $inMultiLineCell = true;
                    continue;
                } else {
                    $this->parseHeaderRow($m[1], $table);
                    continue;
                }
            }

            if (preg_match('/^\|(.*)$/', $line, $m)) {
                if ($inMultiLineCell && !empty($accumulatedCellContent)) {
                    $this->finalizeMultiLineCells($accumulatedCellContent, $currentRow, false);
                    $accumulatedCellContent = [];
                }
                
                $this->parseMultiLineCells($m[1], $accumulatedCellContent, false);
                $inMultiLineCell = true;
                continue;
            }

            if ($inMultiLineCell && $line !== '') {
                $this->appendToMultiLineCells($originalLine, $accumulatedCellContent);
                continue;
            }
            
            if ($inMultiLineCell && $line === '') {
                $this->appendToMultiLineCells('', $accumulatedCellContent);
                continue;
            }
        }

        if (!empty($currentRow)) {
            $table->addRow($currentRow);
        }

        return $table;
    }

    /**
     * Process the table text to handle nested table structures
     */
    private function preprocessNestedTables(string $tableText): array
    {
        $lines = preg_split("/\r?\n/", $tableText);
        $result = [];
        $i = 0;
        
        while ($i < count($lines)) {
            $line = $lines[$i];
            
            // Check if this line contains a nested table start
            if (preg_match('/^(\|.*?\|\|.*?)\{\|(.*)$/', $line, $m)) {
                // Found start of nested table in a cell
                $cellPrefix = trim($m[1]);
                $nestedTableStart = '{|' . ($m[2] ? ' ' . trim($m[2]) : '');
                
                // Collect the complete nested table
                $nestedTableLines = [$nestedTableStart];
                $i++;
                $depth = 1;
                
                while ($i < count($lines) && $depth > 0) {
                    $nestedLine = $lines[$i];
                    
                    if (preg_match('/^\{\|/', $nestedLine)) {
                        $depth++;
                    } elseif (preg_match('/^\|\}/', $nestedLine)) {
                        $depth--;
                    }
                    
                    $nestedTableLines[] = $nestedLine;
                    $i++;
                }
                
                // Create a placeholder for the nested table
                $completeNestedTable = implode("\n", $nestedTableLines);
                $placeholder = "NESTED_TABLE_" . (++self::$nestedTableCounter);
                $this->nestedTablePlaceholders[$placeholder] = $completeNestedTable;
                $result[] = $cellPrefix . $placeholder;
            } else {
                $result[] = $line;
                $i++;
            }
        }
        
        return $result;
    }

    /**
     * Preprocess single-line table format into multi-line format
     */
    private function preprocessSingleLineTable(string $tableText): string
    {
        if (preg_match('/^\{\|(.+?)\|\}$/', trim($tableText), $matches)) {
            $content = trim($matches[1]);
            
            $result = ['{|'];
            
            if (preg_match('/^([^|]*?)(\|.*)$/', $content, $attrMatch)) {
                $attrs = trim($attrMatch[1]);
                $cellContent = $attrMatch[2];
                
                if (!empty($attrs) && !str_starts_with($attrs, '|')) {
                    $result[0] = '{| ' . $attrs;
                    $content = $cellContent;
                } else {
                    $content = $attrMatch[1] . $cellContent;
                }
            }
            
            if (!empty($content)) {
                if (str_starts_with($content, '!')) {
                    $result[] = $content;
                } else {
                    $content = preg_replace('/^\|/', '', $content);
                    
                    $cellParts = explode('||', $content);
                    foreach ($cellParts as $cell) {
                        $cell = trim($cell);
                        if (!empty($cell)) {
                            $result[] = '| ' . $cell;
                        }
                    }
                }
            }
            
            $result[] = '|}';
            return implode("\n", $result);
        }
        
        return $tableText;
    }

    /**
     * Parse a row of cells (either headers or data)
     */
    private function parseRowCells(string $content, ?Table $table, bool $isHeader): array
    {
        $cells = [];
        
        $parts = $this->splitCellContent($content);
        
        foreach ($parts as $cellContent) {
            $cell = $this->parseSingleCell($cellContent, $isHeader);
            
            if ($isHeader && $table) {
                $table->addHeader($cell);
            } else {
                $cells[] = $cell;
            }
        }
        
        return $cells;
    }

    private function splitCellContent(string $content): array
    {
        if (strpos($content, '||') === false) {
            return [trim($content)];
        }
        
        $parts = explode('||', $content);
        $result = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '';
        $tableDepth = 0;

        foreach ($parts as $i => $part) {
            if ($current !== '' && !$inQuotes && $tableDepth === 0) {
                $result[] = trim($current);
                $current = '';
            } elseif ($current !== '') {
                $current .= '||';
            }
            
            $current .= $part;

            $tableDepth += substr_count($part, '{|');
            $tableDepth -= substr_count($part, '|}');

            // Check quote state in this part
            for ($j = 0; $j < strlen($part); $j++) {
                $char = $part[$j];
                if (($char === '"' || $char === "'") && ($j === 0 || $part[$j-1] !== '\\')) {
                    if (!$inQuotes) {
                        $inQuotes = true;
                        $quoteChar = $char;
                    } elseif ($char === $quoteChar) {
                        $inQuotes = false;
                        $quoteChar = '';
                    }
                }
            }

            // If this is the last part or we're not in quotes and not in a table, finalize current
            if ($i === count($parts) - 1 || (!$inQuotes && $tableDepth === 0)) {
                $result[] = trim($current);
                $current = '';
            }
        }

        return $result;
    }

    /**
     * Check if cell content contains a nested table and parse it
     */
    private function checkForNestedTable(Cell $cell, string $content): void
    {
        // First check for placeholders and restore them
        foreach ($this->nestedTablePlaceholders as $placeholder => $nestedTableWikitext) {
            if (strpos($content, $placeholder) !== false) {
                // Parse the nested table using a new parser instance
                $nestedParser = new ParserTable($nestedTableWikitext);
                $nestedTable = $nestedParser->parse();
                
                if ($nestedTable) {
                    $cell->setNestedTable($nestedTable);
                }
                return;
            }
        }
        
        // Check if content contains a nested table pattern {| ... |}
        if (preg_match('/\{\|.*?\|\}/s', $content, $m)) {
            $nestedTableWikitext = $m[0];
            
            // Parse the nested table using a new parser instance
            $nestedParser = new ParserTable($nestedTableWikitext);
            $nestedTable = $nestedParser->parse();
            
            if ($nestedTable) {
                $cell->setNestedTable($nestedTable);
            }
        }
    }    /**
    /**
     * Parse a single cell with attributes
     */
    private function parseSingleCell(string $content, bool $isHeader): Cell
    {
        $content = trim($content);
        
        $content = $this->processNowikiTags($content);
        
        if (preg_match('/^([^|]*?)\|(.*)$/s', $content, $m)) {
            $attrPart = trim($m[1]);
            $cellContent = trim($m[2]);
            
            if ($this->looksLikeAttributes($attrPart)) {
                $attrs = $this->parseSimpleAttributes($attrPart);
                $cell = new Cell($cellContent, $attrs, true);
                $this->checkForNestedTable($cell, $cellContent);
                return $cell;
            }
        }
        
        // No attributes found, entire content is cell content
        $cell = new Cell($content, [], true);
        $this->checkForNestedTable($cell, $content);
        return $cell;
    }

    /**
     * Process nowiki tags to prevent wiki markup interpretation
     */
    private function processNowikiTags(string $content): string
    {
        // Simple nowiki processing: replace <nowiki>content</nowiki> with content
        $content = preg_replace('/<nowiki>(.*?)<\/nowiki>/i', '$1', $content);
        
        // Also handle HTML-encoded nowiki tags
        $content = preg_replace('/&lt;nowiki&gt;(.*?)&lt;\/nowiki&gt;/i', '$1', $content);
        
        return $content;
    }

    /**
     * Check if a string looks like it contains attributes
     */
    private function looksLikeAttributes(string $str): bool
    {
        // Simple heuristic: contains = and looks like key=value
        // Must have word characters followed by = followed by something
        return preg_match('/\w+\s*=\s*["\']?[^=|]*["\']?/', $str) === 1;
    }

    /**
     * Simple attribute parser - no complex regex
     */
    private function parseSimpleAttributes(string $attrString): array
    {
        $attrs = [];
        $attrString = trim($attrString);
        
        if ($attrString === '') {
            return $attrs;
        }

        // Split on spaces, but handle quoted values
        $tokens = $this->tokenizeAttributes($attrString);
        
        foreach ($tokens as $token) {
            if (strpos($token, '=') !== false) {
                [$key, $value] = explode('=', $token, 2);
                $key = trim($key);
                $value = trim($value, '"\'');
                
                // Add semicolon to style attributes if missing
                if ($key === 'style' && !empty($value) && !str_ends_with($value, ';')) {
                    $value .= ';';
                }
                
                if ($key !== '') {
                    $attrs[$key] = $value;
                }
            }
        }

        return $attrs;
    }

    /**
     * Tokenize attribute string handling quoted values
     */
    private function tokenizeAttributes(string $str): array
    {
        $tokens = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];
            
            if (($char === '"' || $char === "'") && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
                $current .= $char;
            } elseif ($inQuotes && $char === $quoteChar) {
                $inQuotes = false;
                $quoteChar = '';
                $current .= $char;
            } elseif ($char === ' ' && !$inQuotes) {
                if (trim($current) !== '') {
                    $tokens[] = trim($current);
                    $current = '';
                }
            } else {
                $current .= $char;
            }
        }
        
        if (trim($current) !== '') {
            $tokens[] = trim($current);
        }
        
        return $tokens;
    }

    /**
     * Parse the initial line of cells and prepare for multi-line accumulation
     */
    private function parseMultiLineCells(string $content, array &$accumulatedCellContent, bool $isHeader): void
    {
        // Reset accumulated content
        $accumulatedCellContent = [];
        
        // Split on || for multiple cells, but be careful with quoted content
        $parts = $this->splitCellContent($content);
        
        foreach ($parts as $cellContent) {
            $accumulatedCellContent[] = [
                'content' => trim($cellContent),
                'isHeader' => $isHeader
            ];
        }
    }

    /**
     * Append content to multi-line cells
     */
    private function appendToMultiLineCells(string $line, array &$accumulatedCellContent): void
    {
        if (empty($accumulatedCellContent)) {
            return;
        }

        // Check if this line starts a new cell with |
        if (preg_match('/^\s*\|(.*)$/', $line, $m)) {
            // This is a new cell, add it to accumulated content
            $accumulatedCellContent[] = [
                'content' => trim($m[1]),
                'isHeader' => false
            ];
        } else {
            // This is continuation of the last cell
            $lastIndex = count($accumulatedCellContent) - 1;
            $accumulatedCellContent[$lastIndex]['content'] .= "\n" . $line;
        }
    }

    /**
     * Finalize multi-line cells and add them to the current row
     */
    private function finalizeMultiLineCells(array $accumulatedCellContent, array &$currentRow, bool $isHeader): void
    {
        foreach ($accumulatedCellContent as $cellData) {
            $content = trim($cellData['content']);
            $cell = $this->parseSingleCell($content, $cellData['isHeader']);
            
            if ($cellData['isHeader']) {
                // For headers, we would typically add them directly to the table
                // but since we're in the middle of parsing, we'll add to current row
                // and let the calling code handle it appropriately
            }
            
            $currentRow[] = $cell;
        }
    }

    /**
     * Parse caption with attributes
     */
    private function parseCaptionWithAttributes(string $content, Table $table): void
    {
        // Check for attributes: pattern is "attr=value attr=value | content"
        if (preg_match('/^([^|]*?)\|(.*)$/s', $content, $m)) {
            $attrPart = trim($m[1]);
            $captionText = trim($m[2]);
            
            // Only treat as attributes if the first part looks like attributes
            if ($this->looksLikeAttributes($attrPart)) {
                $attrs = $this->parseSimpleAttributes($attrPart);
                $table->setCaption($captionText);
                $table->setCaptionAttributes($attrs);
                return;
            }
        }
        
        // No attributes found, entire content is caption
        $table->setCaption($content);
    }

    private function parseHeaderRow(string $content, Table $table): void
    {
        $headerParts = explode('!!', $content);
        
        foreach ($headerParts as $headerPart) {
            $cells = $this->parseRowCells($headerPart, null, true);
            foreach ($cells as $cell) {
                $table->addHeader($cell);
            }
        }
    }

}
