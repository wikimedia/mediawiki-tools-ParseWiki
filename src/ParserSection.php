<?php
namespace WikiConnect\ParseWiki;

use WikiConnect\ParseWiki\DataModel\Section;

/**
 * Class ParserSection
 * Parse a text and extract all sections.
 *
 * @package WikiConnect\ParseWiki
 */
class ParserSection
{
    /**
     * @var string The text to parse for sections.
     */
    private string $text;

    /**
     * @var Section[] Array of extracted sections.
     */
    private array $sections;

    /**
     * ParserSection constructor.
     * @param string $text The text to parse for sections.
     */
    public function __construct(string $text)
    {
        $this->text = $text;
        $this->parse();
    }

    /**
     * Parse the text for sections.
     */
    public function parse(): void
    {
        $sections = [];
        $pattern = '/^(={2,6})\s*(.*?)\s*\1\s*$/m';
        $matches = [];
        preg_match_all($pattern, $this->text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $i => $match) {
            $sectionStartPos = $match[1] + strlen($match[0]);
            $sectionEndPos = $i + 1 < count($matches[0])
                ? $matches[0][$i + 1][1]
                : strlen($this->text);

            $sectionLevel = strlen($matches[1][$i][0] ?? '');
            $sectionTitle = $matches[2][$i][0] ?? '';
            $sectionContent = trim(
                substr($this->text, $sectionStartPos, $sectionEndPos - $sectionStartPos)
            );

            if ($sectionLevel > 0 && $sectionTitle !== '') {
                $sections[] = new Section(
                    $sectionLevel,
                    $sectionTitle,
                    $sectionContent
                );
            }
        }

        $this->sections = $sections;
    }

    /**
     * Get all sections found in the text.
     * @return Section[] An array of Section objects.
     */
    public function getSections(): array
    {
        return $this->sections;
    }
}

