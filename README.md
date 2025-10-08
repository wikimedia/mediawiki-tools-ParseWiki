# WikiConnect ParseWiki

A powerful PHP library for parsing MediaWiki-style content from raw wiki text.

---

## 📚 Overview

This library allows you to extract:
- Templates (single, multiple, nested)
- Internal wiki links
- External links
- Citations (references)
- Categories (with or without display text)
- Tables (complete MediaWiki table syntax support)
Perfect for handling wiki-formatted text in PHP projects.

---

## 🗂️ Project Structure

- `ParserTemplates`: Parses multiple templates.
- `ParserTemplate`: Parses a single template.
- `ParserInternalLinks`: Parses internal wiki links.
- `ParserExternalLinks`: Parses external links.
- `ParserCitations`: Parses citations and references.
- `ParserCategories`: Parses categories from wiki text.
- `ParserTable`: Parses MediaWiki tables with full syntax support.
- `DataModel` classes:
  - `Attribute`
  - `Citation`
  - `ExternalLink`
  - `InternalLink`
  - `Parameters`
  - `Template`
  - `Table`
  - `Cell`
  - `LegacyTableCompatibility` (backward compatibility trait)
- `tests/`: Contains PHPUnit test files:
  - `ParserCategoriesTest`
  - `ParserCitationsTest`
  - `ParserExternalLinksTest`
  - `ParserInternalLinksTest`
  - `ParserSectionTest`
  - `ParserTableTest` (39 tests)
  - `ParserTemplatesTest`
  - `ParserTemplateTest`
  - `DataModel` tests:
    - `AttributeTest`
    - `CellTest`
    - `ParametersTest`
    - `TableTest` (46 tests with 106 assertions)
    - `TemplateTest`
- `demo/`: Live HTML testing interface:
  - `index.html` - Interactive demo frontend
  - `parser.php` - Backend API for real-time parsing
---

## 🚀 Features

- ✅ Parse single and multiple templates.
- ✅ Support nested templates.
- ✅ Handle named and unnamed template parameters.
- ✅ Extract internal links with or without display text.
- ✅ Extract external links with or without labels.
- ✅ Parse citations including attributes and special characters.
- ✅ Parse categories, support custom namespaces, handle whitespaces and special characters.
- ✅ **Full MediaWiki table syntax support** with advanced features:
  - ✅ **Multi-line cell content** (paragraphs, lists, complex markup)
  - ✅ **Rowspan and colspan** attributes with proper span matrix handling
  - ✅ **HTML wrapper detection** (automatically strips `<div>` wrappers)
  - ✅ **Accessibility support** (scope attributes for screen readers)
  - ✅ **Complex attribute parsing** (style, alignment, etc.)
  - ✅ **Nested table support**
  - ✅ **Caption and header attributes**

---
## 🧩 Wikitext Features Support

| Feature                    | Read ✅ | Modify ✏️ | Replace 🔄 |
|--------------------------- |---------|------------|------------|
| **Templates**              | ✅ Yes  | ✅ Yes    | ✅ Yes     |
| **Parameters**             | ✅ Yes  | ✅ Yes    | ✅ Yes     |
| **Citations**              | ✅ Yes  | ✅ Yes    | ✅ Yes     |
| **Citations>Attributes**   | ✅ Yes  | ✅ Yes    | ✅ Yes     |
| **Internal Links**         | ✅ Yes  |     |      |
| **External Links**         | ✅ Yes  |     |      |
| **Categories**             | ✅ Yes  |      |       |
| **Tables**                 | ✅ Yes  | ✅ Yes    | ✅ Yes     |
| **Tables>Attributes**      | ✅ Yes  | ✅ Yes    | ✅ Yes     |
| **Tables>Cells**           | ✅ Yes  | ✅ Yes    | ✅ Yes     |
| **Tables>Headers**         | ✅ Yes  | ✅ Yes    | ✅ Yes     |
| **Tables>Captions**        | ✅ Yes  | ✅ Yes    | ✅ Yes     |
| **Tables>Rowspan/Colspan** | ✅ Yes  | ✅ Yes    | ✅ Yes     |
| **Tables>Multi-line**      | ✅ Yes  | ✅ Yes    | ✅ Yes     |
| **Tables>HTML Wrappers**   | ✅ Yes  | ✅ Yes    | ✅ Yes     |
| **Tables>Nested**          | ✅ Yes  |     |      |
| **HTML Tags**              |   |     |      |
| **Parser Functions**       |   |   |       |
| **Sections**               |   |      |       |
| **Magic Words**            |   |      |       |

> 🟡 **Note:** Some features are partially supported or under development. Contributions are welcome!

---

## 📋 Table Usage Examples

### Basic Table Parsing

```php
use WikiConnect\ParseWiki\ParserTable;

$wikitext = <<<WIKI
{| class="wikitable"
|+ Table Caption
|-
! Header 1 !! Header 2
|-
| Cell 1 || Cell 2
|-
| Cell 3 || Cell 4
|}
WIKI;

$parser = new ParserTable($wikitext);
$table = $parser->parse();

// Access table properties
echo $table->getCaption(); // "Table Caption"
echo $table->getAttributes()['class']; // "wikitable"

// Access headers
$headers = $table->getHeaders();
echo $headers[0]->getContent(); // "Header 1"

// Access data
$rows = $table->getRows();
echo $rows[0][0]->getContent(); // "Cell 1"
```

### Advanced Table Features

```php
// Table with attributes, spans, and accessibility
$complexTable = <<<WIKI
{| class="wikitable" style="width: 100%;"
! scope="col" colspan="2" | Product Info
! scope="col" | Price
|-
! scope="row" | Bread
| 2 loaves
| style="text-align: right;" | $3.50
|-
| rowspan="2" | Dairy
| Milk (1 gallon)
| align="right" | $4.25
|}
WIKI;

$parser = new ParserTable($complexTable);
$table = $parser->parse();

// Check spans
$headers = $table->getHeaders();
echo $headers[0]->getColSpan(); // 2
echo $headers[0]->getScope(); // "col"

// Check alignment
$rows = $table->getRows();
echo $rows[0][2]->getAlign(); // "right"
```

### Multi-line Cell Content

```php
// Table with complex multi-line content
$multiLineTable = <<<WIKI
{|
|Lorem ipsum dolor sit amet,
consetetur sadipscing elitr,
sed diam nonumy eirmod tempor invidunt
ut labore et dolore magna aliquyam erat.

At vero eos et accusam et justo duo dolores
et ea rebum. Stet clita kasd gubergren.
|
* Lorem ipsum dolor sit amet
* consetetur sadipscing elitr
* sed diam nonumy eirmod tempor invidunt
|}
WIKI;

$parser = new ParserTable($multiLineTable);
$table = $parser->parse();

// Access multi-line content
$rows = $table->getRows();
echo $rows[0][0]->getRawContent(); // Full paragraph content
echo $rows[0][1]->getRawContent(); // List content with bullets
```

### HTML Wrapper Support

```php
// Parser automatically handles HTML wrappers
$wrappedTable = <<<HTML
<div class="noresize">
{| class="wikitable"
! colspan="6" |Shopping List
|-
| rowspan="2" |Bread & Butter
| Pie
| Buns
|}
</div>
HTML;

$parser = new ParserTable($wrappedTable);
$table = $parser->parse(); // Automatically strips <div> wrapper

// Access span attributes
$headers = $table->getHeaders();
echo $headers[0]->getAttributes()['colspan']; // "6"

$rows = $table->getRows();
echo $rows[0][0]->getAttributes()['rowspan']; // "2"
```

---

## 🌐 Demo Interface

The library includes HTML demo interface files for testing:

- `demo/index.html` - Interactive frontend interface
- `demo/parser.php` - Backend API for parsing

**Demo Features:**
- ✅ **Real-time parsing** for all parser types
- ✅ **Tabbed output** (Parsed Data, JSON, Methods)
- ✅ **Example library** with pre-built syntax examples  
- ✅ **Responsive design** for desktop and mobile
- ✅ **Statistics display** (cell counts, attributes, etc.)
- ✅ **Error handling** with detailed messages

**Supported Parsers in Demo:**
- Templates Parser
- Single Template Parser  
- **Table Parser** (with full span and multi-line support)
- Internal Links Parser
- External Links Parser
- Citations Parser
- Categories Parser
- Sections Parser

---

## 🆕 Recent Enhancements

### Table Parser Improvements (2025)
- ✅ **Multi-line cell support**: Handles paragraphs, lists, and complex content spanning multiple lines
- ✅ **Rowspan/colspan detection**: Proper span matrix handling for advanced table layouts  
- ✅ **HTML wrapper stripping**: Automatically detects and removes `<div>`, `<span>`, and other HTML wrappers
- ✅ **Enhanced attribute parsing**: Support for complex CSS styles and accessibility attributes
- ✅ **Content consistency**: Unified `getRawContent()` and `getContent()` methods across all data models
- ✅ **100% MediaWiki compatibility**: Tested against all official MediaWiki table examples

### Testing & Development
- ✅ **Comprehensive test suite**: 169 tests with 533 assertions (100% success rate)
- ✅ **TableTest.php**: 46 dedicated tests for Table data model and LegacyTableCompatibility
- ✅ **Live demo interface**: Real-time testing with JSON output and method documentation
- ✅ **Official examples**: Validated against MediaWiki.org documentation examples

---

## ⚙️ Requirements

- PHP 8.0 or higher
- PHPUnit 9 or higher

---

## 💻 Installation

```bash
composer require wiki-connect/parsewiki
```

Make sure you have proper PSR-4 autoloading for the `WikiConnect\ParseWiki` namespace.

---

## 🧪 Running Tests

```bash
vendor/bin/phpunit tests
```