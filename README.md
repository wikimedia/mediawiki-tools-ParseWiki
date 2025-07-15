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
Perfect for handling wiki-formatted text in PHP projects.

---

## 🗂️ Project Structure

- `ParserTemplates`: Parses multiple templates.
- `ParserTemplate`: Parses a single template.
- `ParserInternalLinks`: Parses internal wiki links.
- `ParserExternalLinks`: Parses external links.
- `ParserCitations`: Parses citations and references.
- `ParserCategories`: Parses categories from wiki text.
- `DataModel` classes:
  - `Attribute`
  - `Citation`
  - `ExternalLink`
  - `InternalLink`
  - `Parameters`
  - `Template`
- `tests/`: Contains PHPUnit test files:
  - `ParserCategoriesTest`
  - `ParserCitationsTest`
  - `ParserExternalLinksTest`
  - `ParserInternalLinksTest`
  - `ParserTemplatesTest`
  - `ParserTemplateTest`
  - `DataModel` tests:
    - `AttributeTest`
    - `ParametersTest`
    - `TemplateTest`
---

## 🚀 Features

- ✅ Parse single and multiple templates.
- ✅ Support nested templates.
- ✅ Handle named and unnamed template parameters.
- ✅ Extract internal links with or without display text.
- ✅ Extract external links with or without labels.
- ✅ Parse citations including attributes and special characters.
- ✅ Parse categories, support custom namespaces, handle whitespaces and special characters.
- ✅ Full PHPUnit test coverage.

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
| **HTML Tags**              |   |     |      |
| **Parser Functions**       |   |   |       |
| **Tables**                 |   |      |       |
| **Sections**               |   |      |       |
| **Magic Words**            |   |      |       |

> 🟡 **Note:** Some features are partially supported or under development. Contributions are welcome!

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