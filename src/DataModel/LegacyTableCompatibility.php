<?php

namespace WikiConnect\ParseWiki\DataModel;

/**
 * Trait LegacyTableCompatibility
 * 
 * Provides backward compatibility for the old Table API.
 * This trait can be used to maintain compatibility with code written for earlier versions.
 */
trait LegacyTableCompatibility
{
    /**
     * Get the data as a 2D array (legacy format).
     *
     * @return array The data of the table.
     */
    public function getData(): array
    {
        return array_map(
            function($row) {
                return array_map(
                    function($cell) {
                        return $cell->getRawContent();
                    },
                    $row
                );
            },
            $this->rows
        );
    }

    /**
     * Get a value from the table using the old API format.
     *
     * @param string $key The key to search for in headers.
     * @param int $position The row position.
     *
     * @return string The value at the given position.
     *
     * @throws \InvalidArgumentException If the key does not exist in the header.
     */
    public function get(string $key, int $position): string
    {
        $headerContents = array_map(function($cell) {
            return $cell->getRawContent(); 
        }, $this->headers);

        $keyIndex = array_search($key, $headerContents);
        if ($keyIndex === false) {
            throw new \InvalidArgumentException("The key \"$key\" does not exist in the header.");
        }

        return $this->rows[$position][$keyIndex]->getRawContent();
    }

    /**
     * Set a value in the table using the old API format.
     *
     * @param string $key The key to search for in headers.
     * @param int $position The row position.
     * @param string $value The new value.
     *
     * @throws \InvalidArgumentException If the key does not exist in the header.
     */
    public function setData(string $key, int $position, string $value): void
    {
        $headerContents = array_map(function($cell) {
            return $cell->getRawContent();
        }, $this->headers);

        $keyIndex = array_search($key, $headerContents);
        if ($keyIndex === false) {
            throw new \InvalidArgumentException("The key \"$key\" does not exist in the header.");
        }

        if (!isset($this->rows[$position])) {
            throw new \OutOfBoundsException('Row index out of bounds');
        }

        if (isset($this->rows[$position][$keyIndex])) {
            $this->rows[$position][$keyIndex]->setContent($value);
        }
    }

    /**
     * Legacy constructor compatibility.
     * This should be called from the constructor if legacy parameters are detected.
     *
     * @param array $header The header of the table.
     * @param array $data The data of the table.
     * @param string $classes The classes of the table.
     */
    protected function initFromLegacyFormat(array $header, array $data, string $classes = ""): void
    {
        if ($classes !== "") {
            $this->attrs['class'] = $classes;
        } else {
            $this->attrs['class'] = 'wikitable';
        }

        foreach ($header as $headerText) {
            $this->addHeader(new Cell($headerText));
        }

        foreach ($data as $rowData) {
            $row = [];
            foreach ($rowData as $cellData) {
                $row[] = new Cell((string)$cellData);
            }
            $this->addRow($row);
        }
    }
}