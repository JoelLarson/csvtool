<?php

namespace CSVTool;

/**
 * Interface CSVFile
 * @package CSVTool
 */
interface CSVFile
{
    /**
     * Retrieve the total amount of non-header rows in the file.
     *
     * @return int
     */
    public function totalRows(): int;

    /**
     * Retrieve the total amount of columns defined in the header.
     *
     * @return int
     */
    public function totalColumns(): int;

    /**
     * Remove rows from the file based on a specific filter.
     *
     * @param string $columnName
     * @param string $value
     */
    public function removeRowsByColumn(string $columnName, string $value): void;

    /**
     * Add a row to the end of the file.
     *
     * @param array $elements
     */
    public function addRow(array $elements): void;

    /**
     * Filter rows based on a value for a specific column.
     * @param string $columnName
     * @param string $columnValue
     * @return array
     */
    public function filterByColumn(string $columnName, string $columnValue): array;

    /**
     * Retrieve a row at a specific index.
     *
     * @param int $rowIndex
     * @return array
     */
    public function retrieveRow(int $rowIndex): array;

    /**
     * Replace a row at a specific index.
     *
     * @param int $rowIndex
     * @param array $row
     */
    public function replaceRow(int $rowIndex, array $row): void;

    /**
     * Overwrite values at a specific index.
     *
     * @param int $rowIndex
     * @param array $partialElements
     */
    public function updateRow(int $rowIndex, array $partialElements): void;

    //    public function append(CSVFile $csvFile): array;
}
