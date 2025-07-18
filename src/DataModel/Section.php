<?php
namespace WikiConnect\ParseWiki\DataModel;

/**
 * Class Section
 *
 * Represents a section in a wikitext document.
 *
 * A section is a part of a document that is divided by a heading.
 * The heading is the title of the section and the content is the
 * text that follows the heading.
 *
 * @package WikiConnect\ParseWiki\DataModel
 */
class Section
{
    /**
     * The level of the section.
     *
     * The level is the number of '=' characters that precede the
     * title of the section.
     *
     * @var int
     */
    private $level;
    /**
     * The title of the section.
     *
     * The title is the text that follows the '=' characters.
     *
     * @var string
     */
    private $title;
    /**
     * The content of the section.
     *
     * The content is the text that follows the title of the section.
     *
     * @var string
     */
    private $content;

    /**
     * Section constructor.
     *
     * Creates a new Section object.
     *
     * @param int    $level   The level of the section.
     * @param string $title   The title of the section.
     * @param string $content The content of the section.
     */
    public function __construct(int $level, string $title, string $content)
    {
        $this->level = $level;
        $this->title = $title;
        $this->content = $content;
    }
    /**
     * Get the level of the section.
     *
     * @return int The level of the section.
     */
    public function getLevel(): int
    {
        return $this->level;
    }
    /**
     * Get the title of the section.
     *
     * @return string The title of the section.
     */
    public function getTitle(): string
    {
        return $this->title;
    }
    /**
     * Get the content of the section.
     *
     * @return string The content of the section.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Convert the section to an array.
     *
     * @return array The section as an array.
     */
    public function toArray(): array
    {
        return [
            "level" => $this->level,
            "title" => $this->title,
            "content" => $this->content
        ];
    }
}
