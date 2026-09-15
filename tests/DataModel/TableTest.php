<?php

namespace WikiConnect\ParseWiki\Tests\DataModel;

use PHPUnit\Framework\TestCase;
use WikiConnect\ParseWiki\DataModel\Table;
use WikiConnect\ParseWiki\DataModel\Cell;
use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Table Data Model Test Suite
 * 
 * This comprehensive test suite covers:
 * 1. Table class core functionality
 * 2. LegacyTableCompatibility trait methods
 * 3. Table attributes and metadata
 * 4. Header management
 * 5. Row and cell operations
 * 6. Caption functionality
 * 7. String representation and output
 * 8. Edge cases and error handling
 * 9. Legacy format compatibility
 * 10. Template application
 */
class TableTest extends TestCase
{
    private Table $table;

    protected function setUp(): void
    {
        $this->table = new Table();
    }

    // =============================================================================
    // SECTION 1: BASIC TABLE CONSTRUCTION
    // =============================================================================

    /**
     * Test basic table construction with new format
     */
    public function testNewFormatConstruction(): void
    {
        $attrs = ['class' => 'wikitable', 'style' => 'width: 100%;'];
        $table = new Table($attrs);

        $this->assertEquals($attrs, $table->getAttributes());
    }

    /**
     * Test legacy format constructor compatibility
     */
    public function testLegacyFormatConstruction(): void
    {
        $headers = ['Name', 'Age', 'City'];
        $data = [
            ['Alice', '25', 'New York'],
            ['Bob', '30', 'London']
        ];
        $classes = 'sortable wikitable';

        $table = new Table($headers, $data, $classes);

        // Check attributes
        $this->assertEquals($classes, $table->getAttributes()['class']);

        // Check headers
        $headerCells = $table->getHeaders();
        $this->assertCount(3, $headerCells);
        $this->assertEquals('Name', $headerCells[0]->getContent());
        $this->assertEquals('Age', $headerCells[1]->getContent());
        $this->assertEquals('City', $headerCells[2]->getContent());

        // Check data rows
        $rows = $table->getRows();
        $this->assertCount(2, $rows);
        $this->assertEquals('Alice', $rows[0][0]->getContent());
        $this->assertEquals('30', $rows[1][1]->getContent());
    }

    /**
     * Test legacy format with empty classes defaults to wikitable
     */
    public function testLegacyFormatDefaultClass(): void
    {
        $table = new Table(['Header'], [['Data']], '');

        $this->assertEquals('wikitable', $table->getAttributes()['class']);
    }

    // =============================================================================
    // SECTION 2: ATTRIBUTE MANAGEMENT
    // =============================================================================

    /**
     * Test table attribute setting and retrieval
     */
    public function testTableAttributes(): void
    {
        $attrs = [
            'class' => 'wikitable sortable',
            'style' => 'border-collapse: collapse;',
            'id' => 'main-table'
        ];

        $table = new Table($attrs);

        $this->assertEquals($attrs, $table->getAttributes());
        $this->assertEquals('wikitable sortable', $table->getAttributes()['class']);
        $this->assertEquals('main-table', $table->getAttributes()['id']);
    }

    // =============================================================================
    // SECTION 3: HEADER MANAGEMENT
    // =============================================================================

    /**
     * Test adding and retrieving headers
     */
    public function testHeaderManagement(): void
    {
        $header1 = new Cell('Name');
        $header2 = new Cell('Age');
        $header3 = new Cell('City');

        $this->table->addHeader($header1);
        $this->table->addHeader($header2);
        $this->table->addHeader($header3);

        $headers = $this->table->getHeaders();
        $this->assertCount(3, $headers);
        $this->assertEquals('Name', $headers[0]->getContent());
        $this->assertEquals('Age', $headers[1]->getContent());
        $this->assertEquals('City', $headers[2]->getContent());
    }

    /**
     * Test header accessibility attributes are automatically added
     */
    public function testHeaderAccessibilityAttributes(): void
    {
        $header = new Cell('Test Header');
        $this->table->addHeader($header);

        $headers = $this->table->getHeaders();
        $attrs = $headers[0]->getAttributes();
        $this->assertEquals('col', $attrs['scope']);
    }

    /**
     * Test header with existing scope attribute is preserved
     */
    public function testHeaderExistingScopePreserved(): void
    {
        $header = new Cell('Test Header', ['scope' => 'colgroup']);
        $this->table->addHeader($header);

        $headers = $this->table->getHeaders();
        $attrs = $headers[0]->getAttributes();
        $this->assertEquals('colgroup', $attrs['scope']);
    }

    /**
     * Test header content normalization with escaped pipes
     */
    public function testHeaderContentNormalization(): void
    {
        $header = new Cell('Header<nowiki>|</nowiki>With|Pipe', [], false); // Don't escape HTML
        $this->table->addHeader($header);

        $headers = $this->table->getHeaders();
        $this->assertEquals('Header|With|Pipe', $headers[0]->getContent());
    }

    /**
     * Test header with nested list formatting preservation
     */
    public function testHeaderNestedListFormatting(): void
    {
        $header = new Cell('* Item 1\n# Numbered item', [], false); // Don't escape HTML
        $this->table->addHeader($header);

        $headers = $this->table->getHeaders();
        $content = $headers[0]->getContent();
        $this->assertStringContainsString('* Item 1', $content);
        $this->assertStringContainsString('# Numbered item', $content);
    }

    // =============================================================================
    // SECTION 4: ROW TEMPLATE FUNCTIONALITY
    // =============================================================================

    /**
     * Test setting and applying row templates
     */
    public function testRowTemplate(): void
    {
        $template = new Cell('', ['class' => 'highlight', 'style' => 'background: yellow;']);
        $this->table->setNextRowTemplate($template);

        $row = [
            new Cell('Data1'),
            new Cell('Data2', ['class' => 'special'])
        ];
        $this->table->addRow($row);

        $rows = $this->table->getRows();
        $firstCell = $rows[0][0];
        $secondCell = $rows[0][1];

        // Template attributes should be applied
        $this->assertEquals('highlight', $firstCell->getAttributes()['class']);
        $this->assertEquals('background: yellow;', $firstCell->getAttributes()['style']);

        // Cell-specific attributes should be preserved and merged
        $this->assertEquals('special', $secondCell->getAttributes()['class']);
        $this->assertEquals('background: yellow;', $secondCell->getAttributes()['style']);
    }

    /**
     * Test template applied to headers
     */
    public function testTemplateAppliedToHeaders(): void
    {
        $template = new Cell('', ['class' => 'header-style']);
        $this->table->setNextRowTemplate($template);

        $header = new Cell('Test Header', ['id' => 'header1']);
        $this->table->addHeader($header);

        $headers = $this->table->getHeaders();
        $attrs = $headers[0]->getAttributes();
        
        $this->assertEquals('header-style', $attrs['class']);
        $this->assertEquals('header1', $attrs['id']);
        $this->assertEquals('col', $attrs['scope']); // Default scope still added
    }

    /**
     * Test template is reset after row addition
     */
    public function testTemplateResetAfterRowAddition(): void
    {
        $template = new Cell('', ['class' => 'temp']);
        $this->table->setNextRowTemplate($template);

        // Add first row
        $this->table->addRow([new Cell('Data1')]);

        // Add second row (should not have template)
        $this->table->addRow([new Cell('Data2')]);

        $rows = $this->table->getRows();
        $this->assertEquals('temp', $rows[0][0]->getAttributes()['class']);
        $this->assertArrayNotHasKey('class', $rows[1][0]->getAttributes());
    }

    // =============================================================================
    // SECTION 5: ROW AND CELL OPERATIONS
    // =============================================================================

    /**
     * Test adding and retrieving rows
     */
    public function testRowManagement(): void
    {
        $row1 = [new Cell('Alice'), new Cell('25')];
        $row2 = [new Cell('Bob'), new Cell('30')];

        $this->table->addRow($row1);
        $this->table->addRow($row2);

        $rows = $this->table->getRows();
        $this->assertCount(2, $rows);
        $this->assertCount(2, $rows[0]);
        $this->assertCount(2, $rows[1]);

        $this->assertEquals('Alice', $rows[0][0]->getContent());
        $this->assertEquals('25', $rows[0][1]->getContent());
        $this->assertEquals('Bob', $rows[1][0]->getContent());
        $this->assertEquals('30', $rows[1][1]->getContent());
    }

    /**
     * Test row addition with invalid cell types throws exception
     */
    public function testRowWithInvalidCellType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Row elements must be Cell objects');

        $invalidRow = [new Cell('Valid'), 'Invalid String'];
        $this->table->addRow($invalidRow);
    }

    /**
     * Test cell content normalization with escaped pipes
     */
    public function testCellContentNormalization(): void
    {
        $cell = new Cell('Content<nowiki>|</nowiki>With|Pipe', [], false); // Don't escape HTML
        $this->table->addRow([$cell]);

        $rows = $this->table->getRows();
        $this->assertEquals('Content|With|Pipe', $rows[0][0]->getContent());
    }

    /**
     * Test cell content with nested list preservation
     */
    public function testCellNestedListPreservation(): void
    {
        $cell = new Cell('* Item\n# Number');
        $this->table->addRow([$cell]);

        $rows = $this->table->getRows();
        $content = $rows[0][0]->getContent();
        $this->assertStringContainsString("\n* Item", $content);
    }

    // =============================================================================
    // SECTION 6: CAPTION FUNCTIONALITY
    // =============================================================================

    /**
     * Test setting and retrieving table caption
     */
    public function testCaptionManagement(): void
    {
        $caption = 'Sample Table Caption';
        $this->table->setCaption($caption);

        $this->assertEquals($caption, $this->table->getCaption());
    }

    /**
     * Test caption content normalization
     */
    public function testCaptionContentNormalization(): void
    {
        $caption = 'Caption<nowiki>|</nowiki>With|Pipe';
        $this->table->setCaption($caption);

        $this->assertEquals('Caption|With|Pipe', $this->table->getCaption());
    }

    /**
     * Test caption with nested elements formatting
     */
    public function testCaptionNestedElementsFormatting(): void
    {
        $caption = '* Main caption\n# Sub item';
        $this->table->setCaption($caption);

        $result = $this->table->getCaption();
        $this->assertStringContainsString('* Main caption', $result);
        $this->assertStringContainsString('# Sub item', $result);
    }

    /**
     * Test caption attributes
     */
    public function testCaptionAttributes(): void
    {
        $attrs = [
            'style' => 'font-weight: bold; color: red;',
            'class' => 'table-caption'
        ];

        $this->table->setCaptionAttributes($attrs);

        $captionAttrs = $this->table->getCaptionAttributes();
        $this->assertEquals('font-weight: bold; color: red;', $captionAttrs['style']);
        $this->assertEquals('table-caption', $captionAttrs['class']);
    }

    /**
     * Test caption bottom alignment conversion
     */
    public function testCaptionBottomAlignment(): void
    {
        $this->table->setCaptionAttributes(['align' => 'bottom']);

        $attrs = $this->table->getCaptionAttributes();
        $this->assertEquals('bottom', $attrs['caption-side']);
        $this->assertArrayNotHasKey('align', $attrs);
    }

    /**
     * Test caption style normalization
     */
    public function testCaptionStyleNormalization(): void
    {
        $this->table->setCaptionAttributes(['style' => 'color:red;font-weight:bold']);

        $attrs = $this->table->getCaptionAttributes();
        $this->assertEquals('color: red; font-weight: bold;', $attrs['style']);
    }

    // =============================================================================
    // SECTION 7: STRING REPRESENTATION
    // =============================================================================

    /**
     * Test basic table toString
     */
    public function testBasicToString(): void
    {
        $this->table->addHeader(new Cell('Name'));
        $this->table->addHeader(new Cell('Age'));
        $this->table->addRow([new Cell('Alice'), new Cell('25')]);

        $result = $this->table->toString();

        $this->assertStringContainsString('{|', $result);
        $this->assertStringContainsString('! Name !! Age', $result);
        $this->assertStringContainsString('| Alice || 25', $result);
        $this->assertStringContainsString('|}', $result);
    }

    /**
     * Test toString with table attributes
     */
    public function testToStringWithAttributes(): void
    {
        $table = new Table(['class' => 'wikitable', 'style' => 'width: 100%;']);
        $table->addRow([new Cell('Test')]);

        $result = $table->toString();

        $this->assertStringContainsString('class="wikitable"', $result);
        $this->assertStringContainsString('style="width: 100%;"', $result);
    }

    /**
     * Test toString with caption
     */
    public function testToStringWithCaption(): void
    {
        $this->table->setCaption('Test Caption');
        $this->table->setCaptionAttributes(['style' => 'font-weight: bold;']);

        $result = $this->table->toString();

        $this->assertStringContainsString('|+', $result);
        $this->assertStringContainsString('Test Caption', $result);
        $this->assertStringContainsString('style="font-weight: bold;"', $result);
    }

    /**
     * Test magic __toString method
     */
    public function testMagicToString(): void
    {
        $this->table->addRow([new Cell('Test')]);

        $direct = $this->table->toString();
        $magic = (string) $this->table;

        $this->assertEquals($direct, $magic);
    }

    /**
     * Test toString with empty table
     */
    public function testToStringEmptyTable(): void
    {
        $result = $this->table->toString();

        $this->assertEquals("{|\n|}", $result);
    }

    /**
     * Test toString with caption text alignment normalization
     */
    public function testToStringCaptionTextAlignment(): void
    {
        $this->table->setCaption('Test');
        $this->table->setCaptionAttributes(['style' => 'text-align:center']);

        $result = $this->table->toString();

        $this->assertStringContainsString('text-align: center', $result);
    }

    // =============================================================================
    // SECTION 8: LEGACY COMPATIBILITY METHODS
    // =============================================================================

    /**
     * Test legacy getData method
     */
    public function testLegacyGetData(): void
    {
        $headers = ['Name', 'Age'];
        $data = [['Alice', '25'], ['Bob', '30']];

        $table = new Table($headers, $data);
        $result = $table->getData();

        $this->assertEquals($data, $result);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Alice', $result[0][0]);
        $this->assertEquals('30', $result[1][1]);
    }

    /**
     * Test legacy get method for specific cell values
     */
    public function testLegacyGet(): void
    {
        $headers = ['Name', 'Age', 'City'];
        $data = [
            ['Alice', '25', 'New York'],
            ['Bob', '30', 'London']
        ];

        $table = new Table($headers, $data);

        $this->assertEquals('Alice', $table->get('Name', 0));
        $this->assertEquals('30', $table->get('Age', 1));
        $this->assertEquals('New York', $table->get('City', 0));
        $this->assertEquals('London', $table->get('City', 1));
    }

    /**
     * Test legacy get method throws exception for invalid key
     */
    public function testLegacyGetInvalidKey(): void
    {
        $table = new Table(['Name'], [['Alice']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The key "InvalidKey" does not exist in the header.');

        $table->get('InvalidKey', 0);
    }

    /**
     * Test legacy setData method
     */
    public function testLegacySetData(): void
    {
        $headers = ['Name', 'Age'];
        $data = [['Alice', '25']];

        $table = new Table($headers, $data);
        $table->setData('Name', 0, 'Bob');
        $table->setData('Age', 0, '30');

        $this->assertEquals('Bob', $table->get('Name', 0));
        $this->assertEquals('30', $table->get('Age', 0));
    }

    /**
     * Test legacy setData method throws exception for invalid key
     */
    public function testLegacySetDataInvalidKey(): void
    {
        $table = new Table(['Name'], [['Alice']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The key "InvalidKey" does not exist in the header.');

        $table->setData('InvalidKey', 0, 'Value');
    }

    /**
     * Test legacy setData method throws exception for invalid row index
     */
    public function testLegacySetDataInvalidRowIndex(): void
    {
        $table = new Table(['Name'], [['Alice']]);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Row index out of bounds');

        $table->setData('Name', 5, 'Value');
    }

    /**
     * Test legacy compatibility with mixed operations
     */
    public function testLegacyMixedOperations(): void
    {
        // Start with legacy constructor
        $table = new Table(['Name', 'Score'], [['Alice', '85']], 'sortable');

        // Use new API to add more data
        $table->addRow([new Cell('Bob'), new Cell('92')]);

        // Use legacy API to modify
        $table->setData('Score', 0, '88');

        // Verify using both APIs
        $this->assertEquals('Alice', $table->get('Name', 0));
        $this->assertEquals('88', $table->get('Score', 0));
        $this->assertEquals('Bob', $table->getRows()[1][0]->getContent());

        // Verify legacy getData works
        $data = $table->getData();
        $this->assertEquals('88', $data[0][1]);
        $this->assertEquals('92', $data[1][1]);
    }

    // =============================================================================
    // SECTION 9: STYLE NORMALIZATION
    // =============================================================================

    /**
     * Test style attribute normalization
     */
    public function testStyleNormalization(): void
    {
        $table = new Table();
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($table);
        $method = $reflection->getMethod('normalizeStyleAttribute');

        $input = 'color:red;font-weight:bold;margin:10px';
        $result = $method->invoke($table, $input);

        $this->assertEquals('color: red; font-weight: bold; margin: 10px;', $result);
    }

    /**
     * Test style normalization with background property
     */
    public function testStyleNormalizationBackground(): void
    {
        $table = new Table();
        
        $reflection = new \ReflectionClass($table);
        $method = $reflection->getMethod('normalizeStyleAttribute');

        $input = 'background:#fff url(image.png) repeat';
        $result = $method->invoke($table, $input);

        $this->assertEquals('background: #fff url(image.png) repeat;', $result);
    }

    /**
     * Test style normalization with empty input
     */
    public function testStyleNormalizationEmpty(): void
    {
        $table = new Table();
        
        $reflection = new \ReflectionClass($table);
        $method = $reflection->getMethod('normalizeStyleAttribute');

        $result = $method->invoke($table, '');
        $this->assertEquals('', $result);
    }

    // =============================================================================
    // SECTION 10: EDGE CASES AND ERROR HANDLING
    // =============================================================================

    /**
     * Test table with no headers but with data
     */
    public function testTableNoHeadersWithData(): void
    {
        $this->table->addRow([new Cell('Data1'), new Cell('Data2')]);

        $headers = $this->table->getHeaders();
        $rows = $this->table->getRows();

        $this->assertCount(0, $headers);
        $this->assertCount(1, $rows);
    }

    /**
     * Test table with headers but no data
     */
    public function testTableHeadersNoData(): void
    {
        $this->table->addHeader(new Cell('Header1'));
        $this->table->addHeader(new Cell('Header2'));

        $headers = $this->table->getHeaders();
        $rows = $this->table->getRows();

        $this->assertCount(2, $headers);
        $this->assertCount(0, $rows);
    }

    /**
     * Test null caption handling
     */
    public function testNullCaptionHandling(): void
    {
        $caption = $this->table->getCaption();
        $this->assertNull($caption);

        $result = $this->table->toString();
        $this->assertStringNotContainsString('|+', $result);
    }

    /**
     * Test empty row addition
     */
    public function testEmptyRowAddition(): void
    {
        $this->table->addRow([]);

        $rows = $this->table->getRows();
        $this->assertCount(1, $rows);
        $this->assertCount(0, $rows[0]);
    }

    /**
     * Test template clearing with null
     */
    public function testTemplateClearingWithNull(): void
    {
        $template = new Cell('', ['class' => 'test']);
        $this->table->setNextRowTemplate($template);
        $this->table->setNextRowTemplate(null);

        $this->table->addRow([new Cell('Test')]);

        $rows = $this->table->getRows();
        $this->assertArrayNotHasKey('class', $rows[0][0]->getAttributes());
    }

    /**
     * Test complex caption with multiple lines and formatting
     */
    public function testComplexCaptionFormatting(): void
    {
        $caption = "Table Caption\n* With list\n# And numbers\nMore text";
        $this->table->setCaption($caption);

        $result = $this->table->getCaption();
        $this->assertStringContainsString("\n* With list", $result);
        $this->assertStringContainsString("\n# And numbers", $result);
    }

    /**
     * Test legacy constructor with empty data array
     */
    public function testLegacyConstructorEmptyData(): void
    {
        $table = new Table(['Header1', 'Header2'], [], 'test-class');

        $this->assertCount(2, $table->getHeaders());
        $this->assertCount(0, $table->getRows());
        $this->assertEquals('test-class', $table->getAttributes()['class']);
    }

    /**
     * Test legacy constructor with mismatched header and data columns
     */
    public function testLegacyConstructorMismatchedColumns(): void
    {
        $headers = ['Col1', 'Col2', 'Col3'];
        $data = [
            ['A', 'B'], // Missing third column
            ['X', 'Y', 'Z', 'Extra'] // Extra column
        ];

        $table = new Table($headers, $data);

        $this->assertCount(3, $table->getHeaders());
        $this->assertCount(2, $table->getRows());
        
        // First row should have 2 cells
        $this->assertCount(2, $table->getRows()[0]);
        // Second row should have 4 cells
        $this->assertCount(4, $table->getRows()[1]);
    }
}
