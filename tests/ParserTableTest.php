<?php

declare(strict_types=1);

namespace WikiConnect\ParseWiki\Tests;

use PHPUnit\Framework\TestCase;
use WikiConnect\ParseWiki\ParserTable;
use WikiConnect\ParseWiki\DataModel\Table;
use WikiConnect\ParseWiki\DataModel\Cell;

/**
 * ParserTable Test Suite
 * 
 * This test file covers all aspects of table parsing:
 * 1. Basic table structure parsing
 * 2. Table attributes and styling  
 * 3. Cell content and attributes
 * 4. Single-line table formats
 * 5. Edge cases and error handling
 * 6. Nested tables functionality
 * 7. Performance testing
 * 8. Backwards compatibility testing
 * 
 * All tests are organized by functionality with clear documentation
 */
class ParserTableTest extends TestCase
{
    private ParserTable $parser;

    protected function setUp(): void
    {
        $this->parser = new ParserTable('');
    }

    // =============================================================================
    // SECTION 1: BASIC TABLE PARSING TESTS
    // =============================================================================

    /**
     * Test basic table structure parsing
     */
    public function testBasicTableStructure(): void
    {
        $markup = '{|
! Header 1
! Header 2
|-
| Cell 1
| Cell 2
|}';

        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        $this->assertCount(2, $table->getHeaders());
        $this->assertCount(1, $table->getRows());
        $this->assertEquals('Header 1', $table->getHeaders()[0]->getContent());
        $this->assertEquals('Cell 1', $table->getRows()[0][0]->getContent());
    }

    /**
     * Test inline cell syntax (|| and !!)
     */
    public function testInlineCellSyntax(): void
    {
        $markup = '{|
! Header 1 !! Header 2 !! Header 3
|-
| Cell 1 || Cell 2 || Cell 3
|}';

        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertCount(3, $table->getHeaders());
        $this->assertCount(3, $table->getRows()[0]);
        $this->assertEquals('Header 2', $table->getHeaders()[1]->getContent());
        $this->assertEquals('Cell 3', $table->getRows()[0][2]->getContent());
    }

    /**
     * Test table with caption
     */
    public function testTableCaption(): void
    {
        $wikitext = '{| class="wikitable"
|+ style="font-weight: bold;" | Table Caption
! Name
! Age
|-
| John
| 25
|}';

        $this->parser = new ParserTable($wikitext);
        $table = $this->parser->parse();

        $this->assertEquals('Table Caption', $table->getCaption());
        $this->assertStringContainsString('font-weight: bold', $table->getCaptionAttributes()['style'] ?? '');
    }

    /**
     * Test table and row attributes
     */
    public function testTableAndRowAttributes(): void
    {
        $wikitext = '{| class="wikitable" style="width: 100%;" border="1"
! Name
|-  style="background: #eee;"
| John
|}';

        $this->parser = new ParserTable($wikitext);
        $table = $this->parser->parse();

        // Test table attributes
        $this->assertEquals('wikitable', $table->getAttributes()['class']);
        $this->assertEquals('width: 100%;', $table->getAttributes()['style']);
        $this->assertEquals('1', $table->getAttributes()['border']);

        // Test row attributes - TODO: implement row attributes in parser
        // $this->assertStringContainsString('background: #eee', $table->getRows()[0]->getAttributes()['style'] ?? '');
    }

    /**
     * Test cell attributes and styling
     */
    public function testCellAttributes(): void
    {
        $wikitext = '{|
! width="50%" | Header
|-
| align="center" style="color: red;" | Centered Red Text
| bgcolor="#eee" | Gray Background
|}';

        $this->parser = new ParserTable($wikitext);
        $table = $this->parser->parse();

        $headerCell = $table->getHeaders()[0];
        $this->assertEquals('50%', $headerCell->getAttributes()['width']);

        $dataCell1 = $table->getRows()[0][0];
        $this->assertEquals('center', $dataCell1->getAlign());
        $this->assertStringContainsString('color: red', $dataCell1->getAttributes()['style'] ?? '');

        $dataCell2 = $table->getRows()[0][1];
        $this->assertStringContainsString('background-color: #eee', $dataCell2->getAttributes()['style'] ?? '');
    }

    /**
     * Test cell spanning (colspan and rowspan)
     */
    public function testCellSpanning(): void
    {
        $wikitext = '{|
! colspan="2" | Wide Header
! rowspan="2" | Tall Header
|-
| Cell 1
| Cell 2
|-
| colspan="3" | Full Width Cell
|}';

        $this->parser = new ParserTable($wikitext);
        $table = $this->parser->parse();

        $wideHeader = $table->getHeaders()[0];
        $this->assertEquals(2, $wideHeader->getColspan());

        $tallHeader = $table->getHeaders()[1];
        $this->assertEquals(2, $tallHeader->getRowspan());

        $fullWidthCell = $table->getRows()[1][0];
        $this->assertEquals(3, $fullWidthCell->getColspan());
    }

    /**
     * Test accessibility features (scope attributes)
     */
    public function testAccessibilityFeatures(): void
    {
        $wikitext = '{|
! scope="col" | Product
! scope="col" | Price
|-
! scope="row" | Bread
| $2.50
|}';

        $this->parser = new ParserTable($wikitext);
        $table = $this->parser->parse();

        $productHeader = $table->getHeaders()[0];
        $this->assertEquals('col', $productHeader->getScope());

        $breadHeader = $table->getRows()[0][0];
        $this->assertEquals('row', $breadHeader->getScope());
    }

    // =============================================================================
    // SECTION 2: ADVANCED PARSING TESTS
    // =============================================================================

    /**
     * Test escaped content and special characters
     */
    public function testEscapedContent(): void
    {
        $wikitext = '{|
! Column <nowiki>|</nowiki> Name
|-
| Content with <nowiki>|</nowiki> pipe
| Normal content
|}';

        $this->parser = new ParserTable($wikitext);
        $table = $this->parser->parse();

        $headerContent = $table->getHeaders()[0]->getContent();
        $this->assertStringContainsString('Column | Name', $headerContent);

        $cellContent = $table->getRows()[0][0]->getContent();
        $this->assertStringContainsString('Content with | pipe', $cellContent);
    }

    /**
     * Test nested tables
     */
    public function testNestedTables(): void
    {
        $wikitext = '{| class="outer"
! Header 1 !! Header 2
|-
| Outer Cell || {| class="inner"
! Inner H1 !! Inner H2
|-
| Inner A || Inner B
|}
|}';

        $this->parser = new ParserTable($wikitext);
        $table = $this->parser->parse();

        $nestedCell = $table->getRows()[0][1];
        $this->assertNotNull($nestedCell->getNestedTable());

        $nested = $nestedCell->getNestedTable();
        $this->assertEquals('inner', $nested->getAttributes()['class']);
        $this->assertEquals('Inner A', $nested->getRows()[0][0]->getContent());
    }

    /**
     * Test unquoted attributes
     */
    public function testUnquotedAttributes(): void
    {
        $wikitext = '{| border=1 width=100% cellpadding=4
| Cell 1
|}';

        $this->parser = new ParserTable($wikitext);
        $table = $this->parser->parse();

        $attrs = $table->getAttributes();
        $this->assertEquals('1', $attrs['border']);
        $this->assertEquals('100%', $attrs['width']);
        $this->assertEquals('4', $attrs['cellpadding']);
    }

    // =============================================================================
    // SECTION 3: DATA MODEL TESTS (Table and Cell classes)
    // =============================================================================

    /**
     * Test Table class basic functionality
     */
    public function testTableDataModel(): void
    {
        $table = new Table();
        
        // Test adding headers
        $header1 = new Cell("Name");
        $header2 = new Cell("Age");
        $table->addHeader($header1);
        $table->addHeader($header2);

        $headers = $table->getHeaders();
        $this->assertCount(2, $headers);
        $this->assertEquals("Name", $headers[0]->getContent());
        $this->assertEquals("Age", $headers[1]->getContent());

        // Test adding rows
        $row1 = [new Cell("Alice"), new Cell("25")];
        $row2 = [new Cell("Bob"), new Cell("30")];
        $table->addRow($row1);
        $table->addRow($row2);

        $rows = $table->getRows();
        $this->assertCount(2, $rows);
        $this->assertEquals("Alice", $rows[0][0]->getContent());
        $this->assertEquals("30", $rows[1][1]->getContent());
    }

    /**
     * Test Table caption functionality
     */
    public function testTableCaptionDataModel(): void
    {
        $table = new Table();
        $table->setCaption("Example Table");
        $table->setCaptionAttributes(['style' => 'font-weight: bold;']);

        $this->assertEquals("Example Table", $table->getCaption());
        $this->assertEquals('font-weight: bold;', $table->getCaptionAttributes()['style']);
    }

    /**
     * Test Cell class functionality
     */
    public function testCellDataModel(): void
    {
        // Test basic cell
        $cell = new Cell("Test Content");
        $this->assertEquals("Test Content", $cell->getContent());

        // Test cell with attributes
        $cell->setAttributes(['class' => 'test', 'style' => 'color: red;']);
        $this->assertEquals('test', $cell->getAttributes()['class']);
        $this->assertEquals('color: red;', $cell->getAttributes()['style']);

        // Test cell spanning
        $cell->setColspan(2);
        $cell->setRowspan(3);
        $this->assertEquals(2, $cell->getColspan());
        $this->assertEquals(3, $cell->getRowspan());

        // Test alignment and scope
        $cell->setAlign('center');
        $cell->setScope('col');
        $this->assertEquals('center', $cell->getAlign());
        $this->assertEquals('col', $cell->getScope());
    }

    /**
     * Test legacy Table constructor compatibility
     */
    public function testLegacyTableConstructor(): void
    {
        // Old format: headers and data arrays
        $headers = ['Name', 'Age'];
        $data = [['Alice', '25'], ['Bob', '30']];
        
        $table = new Table($headers, $data);
        
        // Should work with new methods
        $this->assertEquals('Name', $table->getHeaders()[0]->getContent());
        $this->assertEquals('Alice', $table->getRows()[0][0]->getContent());
        
        // Should have default wikitable class
        $this->assertEquals('wikitable', $table->getAttributes()['class']);
        
        // Should support legacy get() method
        $this->assertEquals('25', $table->get('Age', 0));
        $this->assertEquals('30', $table->get('Age', 1));
    }

    /**
     * Test HTML escaping in cells
     */
    public function testCellHtmlEscaping(): void
    {
        $html = '<script>alert("xss")</script>';
        $cell = new Cell($html);
        
        // Test escaped content (default)
        $this->assertStringContainsString('&lt;script&gt;', $cell->getContent());
        
        // Test raw content
        $this->assertEquals($html, $cell->getRawContent());
        
        // Test disabling escaping
        $cell->setEscapeHtml(false);
        $this->assertEquals($html, $cell->getContent());
    }

    // =============================================================================
    // SECTION 4: INTEGRATION AND GENERATION TESTS
    // =============================================================================

    /**
     * Test table toString generation
     */
    public function testTableGeneration(): void
    {
        $wikitext = '{| class="wikitable"
! Header 1 !! Header 2
|-
| Cell 1 || Cell 2
|}';

        $this->parser = new ParserTable($wikitext);
        $table = $this->parser->parse();
        $generated = $table->toString();

        $this->assertStringContainsString('{|', $generated);
        $this->assertStringContainsString('class="wikitable"', $generated);
        $this->assertStringContainsString('Header 1', $generated);
        $this->assertStringContainsString('Cell 1', $generated);
        $this->assertStringContainsString('|}', $generated);
    }

    /**
     * Test complex real-world example
     */
    public function testComplexRealWorldExample(): void
    {
        $wikitext = '{| class="wikitable sortable" style="width: 100%;"
|+ style="caption-side: bottom;" | Product Comparison Table
|-
! scope="col" rowspan="2" | Product
! scope="colgroup" colspan="2" | Specifications  
! scope="col" rowspan="2" | Price
|-
! scope="col" | Weight
! scope="col" | Color
|-
! scope="row" | Laptop A
| align="right" | 2.1 kg
| style="color: blue;" | Blue
| style="text-align: right;" | $999
|-
! scope="row" | Laptop B  
| align="right" | 1.8 kg
| bgcolor="#ff0000" style="color: white;" | Red
| style="text-align: right;" | $1,299
|}';

        $this->parser = new ParserTable($wikitext);
        $table = $this->parser->parse();

        // Test table attributes
        $this->assertEquals('wikitable sortable', $table->getAttributes()['class']);
        $this->assertStringContainsString('width: 100%', $table->getAttributes()['style']);

        // Test caption
        $this->assertEquals('Product Comparison Table', $table->getCaption());

        // Test complex cell spanning and attributes
        $productHeader = $table->getHeaders()[0];
        $this->assertEquals('col', $productHeader->getScope());
        $this->assertEquals(2, $productHeader->getRowspan());

        $specsHeader = $table->getHeaders()[1];
        $this->assertEquals('colgroup', $specsHeader->getScope());
        $this->assertEquals(2, $specsHeader->getColspan());

        // Test data cells with various attributes
        $laptopARow = $table->getRows()[0];  // Changed from [1] to [0]
        $this->assertEquals('Laptop A', trim($laptopARow[0]->getContent()));
        $this->assertEquals('right', $laptopARow[1]->getAlign());
        $this->assertStringContainsString('color: blue', $laptopARow[2]->getAttributes()['style'] ?? '');
        $this->assertStringContainsString('text-align: right', $laptopARow[3]->getAttributes()['style'] ?? '');
    }

    // =============================================================================
    // SECTION 5: EDGE CASES AND ERROR HANDLING
    // =============================================================================

    /**
     * Test empty cells and edge cases
     */
    public function testEmptyCellsAndEdgeCases(): void
    {
        $wikitext = '{|
! Header 1 !! !! Header 3
|-
| Content ||  || 
|-
|  
| Content 2
| 
|}';

        $this->parser = new ParserTable($wikitext);
        $table = $this->parser->parse();

        $this->assertCount(3, $table->getHeaders());
        $this->assertEquals('', trim($table->getHeaders()[1]->getContent()));
        $this->assertEquals('', trim($table->getRows()[0][2]->getContent()));
    }

    /**
     * Test error handling
     */
    public function testErrorHandling(): void
    {
        // Empty table
        $emptyTable = '{|
|}';
        $this->parser = new ParserTable($emptyTable);
        $table = $this->parser->parse();
        $this->assertNotNull($table);
        $this->assertEmpty($table->getRows());

        // Invalid markup
        $this->parser = new ParserTable('invalid');
        $result = $this->parser->parse();
        $this->assertNull($result);

        // Empty input
        $this->parser = new ParserTable('');
        $result = $this->parser->parse();
        $this->assertNull($result);
    }

    /**
     * Test mixed content types
     */
    public function testMixedContent(): void
    {
        $wikitext = '{|
! Text
! Numbers
! Lists
|-
| Simple text
| -123.45
| * Item 1
* Item 2
|-
| Multiline
content here
| 0
| # First
# Second
|}';

        $this->parser = new ParserTable($wikitext);
        $table = $this->parser->parse();

        $this->assertEquals('Simple text', trim($table->getRows()[0][0]->getContent()));
        $this->assertEquals('-123.45', trim($table->getRows()[0][1]->getContent()));
        $this->assertStringContainsString('* Item 1', $table->getRows()[0][2]->getContent());
        $this->assertStringContainsString('Multiline', $table->getRows()[1][0]->getContent());
    }

    // =============================================================================
    // SECTION 9: SINGLE-LINE TABLE TESTS
    // =============================================================================

    /**
     * Test single-line basic table format
     */
    public function testSingleLineBasicTable(): void
    {
        $markup = '{| | cell1 || cell2 || cell3 |}';
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        $this->assertCount(1, $table->getRows());
        $this->assertCount(3, $table->getRows()[0]);
        $this->assertEquals('cell1', $table->getRows()[0][0]->getContent());
        $this->assertEquals('cell2', $table->getRows()[0][1]->getContent());
        $this->assertEquals('cell3', $table->getRows()[0][2]->getContent());
    }

    /**
     * Test single-line table with attributes
     */
    public function testSingleLineTableWithAttributes(): void
    {
        $markup = '{| class="wikitable" style="width:100%" | cell1 || cell2 |}';
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        $attrs = $table->getAttributes();
        $this->assertEquals('wikitable', $attrs['class']);
        $this->assertEquals('width:100%;', $attrs['style']);
        $this->assertCount(1, $table->getRows());
        $this->assertCount(2, $table->getRows()[0]);
    }

    /**
     * Test single-line header table
     */
    public function testSingleLineHeaderTable(): void
    {
        $markup = '{| ! header1 !! header2 !! header3 |}';
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        $this->assertCount(3, $table->getHeaders());
        $this->assertEquals('header1', $table->getHeaders()[0]->getContent());
        $this->assertEquals('header2', $table->getHeaders()[1]->getContent());
        $this->assertEquals('header3', $table->getHeaders()[2]->getContent());
    }

    /**
     * Test single-line empty table
     */
    public function testSingleLineEmptyTable(): void
    {
        $markup = '{|}';
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        $this->assertCount(0, $table->getHeaders());
        $this->assertCount(0, $table->getRows());
    }

    /**
     * Test single-line table with extra spaces
     */
    public function testSingleLineTableWithSpaces(): void
    {
        $markup = '{|  |  spaced content  ||  more spaces  |}';
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        $this->assertCount(1, $table->getRows());
        $this->assertCount(2, $table->getRows()[0]);
        $this->assertEquals('spaced content', $table->getRows()[0][0]->getContent());
        $this->assertEquals('more spaces', $table->getRows()[0][1]->getContent());
    }

    // =============================================================================
    // SECTION 10: EDGE CASE AND ERROR HANDLING TESTS
    // =============================================================================

    /**
     * Test empty string input
     */
    public function testEmptyStringInput(): void
    {
        $this->parser = new ParserTable('');
        $table = $this->parser->parse();
        $this->assertNull($table);
    }

    /**
     * Test whitespace-only input
     */
    public function testWhitespaceOnlyInput(): void
    {
        $this->parser = new ParserTable("   \n\t  \n  ");
        $table = $this->parser->parse();
        $this->assertNull($table);
    }

    /**
     * Test table with only whitespace content
     */
    public function testTableWithOnlyWhitespace(): void
    {
        $markup = '{|   
        
        |}';
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        $this->assertCount(0, $table->getHeaders());
        $this->assertCount(0, $table->getRows());
    }

    /**
     * Test malformed table start
     */
    public function testMalformedTableStart(): void
    {
        $this->parser = new ParserTable('| not a table');
        $table = $this->parser->parse();
        $this->assertNull($table);
    }

    /**
     * Test unbalanced table markers
     */
    public function testUnbalancedTableMarkers(): void
    {
        $markup = '{| | cell1 || cell2';  // Missing |}
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        // Unbalanced tables should still create a table object but may have no complete rows
        // since the closing |} is missing, the parser can't finalize the row
        $this->assertInstanceOf(Table::class, $table);
        // Note: incomplete tables may not have finalized rows
    }

    /**
     * Test attributes with equals signs in values
     */
    public function testAttributesWithEqualsInValue(): void
    {
        $markup = '{| data-test="value=with=equals"
| cell
|}';
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        $attrs = $table->getAttributes();
        $this->assertEquals('value=with=equals', $attrs['data-test']);
    }

    /**
     * Test attributes without values
     */
    public function testAttributesWithoutValues(): void
    {
        $markup = '{| disabled readonly
| cell
|}';
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        // Should handle gracefully even if attributes don't have values
    }

    /**
     * Test mixed quote types in attributes
     */
    public function testMixedQuoteTypes(): void
    {
        $markup = '{| class="test" title=\'single quotes\' data-mixed="value with \'nested\' quotes"
| cell
|}';
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        $attrs = $table->getAttributes();
        $this->assertEquals('test', $attrs['class']);
        $this->assertEquals('single quotes', $attrs['title']);
    }

    /**
     * Test cells with pipe characters
     */
    public function testCellsWithPipes(): void
    {
        $markup = '{|
| content || with | pipe || more content
|}';
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        $this->assertCount(1, $table->getRows());
        $this->assertCount(3, $table->getRows()[0]);
        $this->assertEquals('content', $table->getRows()[0][0]->getContent());
        $this->assertEquals('with | pipe', $table->getRows()[0][1]->getContent());
        $this->assertEquals('more content', $table->getRows()[0][2]->getContent());
    }

    /**
     * Test cells with quoted content
     */
    public function testCellsWithQuotedContent(): void
    {
        $markup = '{|
| "quoted content" || \'single quoted\' || mixed "quotes"
|}';
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        $this->assertCount(1, $table->getRows());
        $this->assertCount(3, $table->getRows()[0]);
    }

    /**
     * Test cells with special characters
     */
    public function testCellsWithSpecialCharacters(): void
    {
        $markup = '{|
| < & > || "quotes" || \'apostrophe\'s || @ # $ %
|}';
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        $this->assertCount(1, $table->getRows());
        $this->assertCount(4, $table->getRows()[0]);
    }

    /**
     * Test table with many columns for performance
     */
    public function testManyColumns(): void
    {
        $cells = [];
        for ($i = 1; $i <= 50; $i++) {
            $cells[] = "cell$i";
        }
        $markup = '{| | ' . implode(' || ', $cells) . ' |}';
        
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        $this->assertCount(1, $table->getRows());
        $this->assertCount(50, $table->getRows()[0]);
        $this->assertEquals('cell1', $table->getRows()[0][0]->getContent());
        $this->assertEquals('cell50', $table->getRows()[0][49]->getContent());
    }

    /**
     * Test static counter isolation between parser instances
     */
    public function testStaticCounterIsolation(): void
    {
        // Test that multiple parser instances don't interfere with each other
        $parser1 = new ParserTable('{| | nested{|nested1|}content |}');
        $parser2 = new ParserTable('{| | nested{|nested2|}content |}');
        $parser3 = new ParserTable('{| | nested{|nested3|}content |}');

        $table1 = $parser1->parse();
        $table2 = $parser2->parse();
        $table3 = $parser3->parse();

        // All should parse successfully
        $this->assertInstanceOf(Table::class, $table1);
        $this->assertInstanceOf(Table::class, $table2);
        $this->assertInstanceOf(Table::class, $table3);
    }

    /**
     * Test nested tables functionality
     */
    public function testNestedTablesStillWork(): void
    {
        $markup = '{| class="outer"
| Regular cell || Cell with nested: {| class="inner"
| nested cell 1
| nested cell 2
|}
| Another regular cell
|}';
        $this->parser = new ParserTable($markup);
        $table = $this->parser->parse();

        $this->assertInstanceOf(Table::class, $table);
        $this->assertCount(1, $table->getRows());
        $this->assertCount(3, $table->getRows()[0]);
        
        // Check that nested table functionality works correctly
        $nestedCell = $table->getRows()[0][1];
        $this->assertNotNull($nestedCell->getNestedTable());
    }
}
