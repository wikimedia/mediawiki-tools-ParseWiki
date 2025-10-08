<?php

namespace WikiConnect\ParseWiki\Tests\DataModel;

use PHPUnit\Framework\TestCase;
use WikiConnect\ParseWiki\DataModel\Cell;

/**
 * Cell Test Suite
 * 
 * Tests the Cell data model class functionality including:
 * - Cell content handling
 * - Attribute management
 * - Style shortcuts
 * - Data sorting attributes
 */
class CellTest extends TestCase
{
    public function testSortKeys(): void
    {
        $cell = new Cell('123', [
            'data-sort-type' => 'number',
            'data-sort-value' => '123'
        ]);
        $attrs = $cell->getAttributes();
        $this->assertEquals('number', $attrs['data-sort-type']);
        $this->assertEquals('123', $attrs['data-sort-value']);
    }

    public function testStyleShortcuts(): void
    {
        $cell = new Cell('Test', [
            'bgcolor' => '#eee',
            'width' => '50%',
            'color' => 'red'
        ]);
        $attrs = $cell->getAttributes();
        $this->assertStringContainsString('background-color: #eee', $attrs['style']);
        $this->assertStringContainsString('width: 50%', $attrs['style']);
        $this->assertStringContainsString('color: red', $attrs['style']);
    }

    public function testOptionalRowStart(): void
    {
        $cell = new Cell('Test');
        $this->assertEquals('Test', $cell->getContent());
    }
}