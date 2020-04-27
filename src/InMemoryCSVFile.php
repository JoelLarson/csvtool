<?php

namespace CSVTool;

/**
 * Class InMemoryCSVFile
 * @package CSVTool
 */
class InMemoryCSVFile implements CSVFile
{
    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var array[]
     */
    protected $contents;

    /**
     * InMemoryCSVFile constructor.
     * @param $filePath
     */
    public function __construct($filePath)
    {
        $this->filePath = realpath($filePath);

        $this->contents = array_map(
            static function ($row) {
                return str_getcsv($row);
            },
            file($this->filePath)
        );
    }

    /**
     * @return int
     */
    public function totalRows(): int
    {
        return count($this->contents) - 1;
    }

    /**
     * @return int
     */
    public function totalColumns(): int
    {
        return count($this->columns());
    }

    /**
     * @param string $columnName
     * @param string $value
     */
    public function removeRowsByColumn(string $columnName, string $value): void
    {
        $columnIndex = array_search($columnName, $this->columns(), true);

        $this->contents = array_filter(
            $this->contents,
            static function ($row) use ($columnIndex, $value) {
                return $row[$columnIndex] !== $value;
            }
        );

        $this->commit();
    }

    /**
     * @return array
     */
    private function columns(): array
    {
        return $this->contents[0];
    }

    /**
     * @param array $elements
     */
    public function addRow(array $elements): void
    {
        $this->contents[] = $this->convertToCSVRow($elements);

        $this->commit();
    }

    // todo add bounds exception

    /**
     * @param string $columnName
     * @param string $columnValue
     * @return array
     */
    public function filterByColumn(string $columnName, string $columnValue): array
    {
        $columnIndex = array_search($columnName, $this->columns(), true);

        return array_filter(
            $this->contents,
            static function ($row) use ($columnIndex, $columnValue) {
                return $row[$columnIndex] === $columnValue;
            }
        );
    }

    /**
     * @param int $rowIndex
     * @return array
     */
    public function retrieveRow(int $rowIndex): array
    {
        return $this->contents[$this->adjustIndex($rowIndex)];
    }

    /**
     * @param int $rowIndex
     * @param array $row
     */
    public function replaceRow(int $rowIndex, array $row): void
    {
        $this->contents[$this->adjustIndex($rowIndex)] = $this->convertToCSVRow($row);

        $this->commit();
    }

    /**
     * @param $elements
     * @return array
     */
    private function convertToCSVRow($elements): array
    {
        return array_map(
            static function ($columnName) use ($elements) {
                return array_key_exists($columnName, $elements) ? $elements[$columnName] : '';
            },
            $this->columns()
        );
    }

    /**
     * @param int $rowIndex
     * @param array $partialElements
     */
    public function updateRow(int $rowIndex, array $partialElements): void
    {
        $columnIndexes = array_flip($this->columns());

        foreach (array_keys($partialElements) as $partialColumn) {
            if (!array_key_exists($partialColumn, $columnIndexes)) {
                throw new \OutOfBoundsException('The partial elements array contains an invalid column name');
            }
        }

        $originalRow = $this->retrieveRow($rowIndex);

        foreach ($partialElements as $columnName => $columnValue) {
            $originalRow[$columnIndexes[$columnName]] = $columnValue;
        }

        $this->writeToRow($rowIndex, $originalRow);
    }

    /**
     * @param int $index
     * @return int
     */
    private function adjustIndex(int $index): int
    {
        $adjustedIndex = $index + 1;

        if (!array_key_exists($adjustedIndex, $this->contents)) {
            throw new \OutOfBoundsException('The retrieved row is out of bounds.');
        }
        return $adjustedIndex;
    }

    /**
     * @param $rowIndex
     * @param $row
     */
    private function writeToRow($rowIndex, $row): void
    {
        $this->contents[$this->adjustIndex($rowIndex)] = $row;
    }

    /**
     * @param InMemoryCSVFile $csvFile
     * @return array
     */
    public function append(InMemoryCSVFile $csvFile): array
    {
        $primaryColumn = $this->primaryColumn();

        $uniqueIndex = array_map(
            static function ($row) use ($primaryColumn) {
                return $row[$primaryColumn];
            },
            $this->contents
        );

        $duplicateRows = [];

        foreach ($csvFile->contents as $appendingRow) {
            if (in_array($appendingRow[$primaryColumn], $uniqueIndex, true)) {
                $duplicateRows[] = $appendingRow;
                continue;
            }

            $this->contents[] = $appendingRow;
        }

        return $duplicateRows;
    }

    /**
     * @return int
     */
    private function primaryColumn(): int
    {
        return 0;
    }

    /**
     * Commit the in-memory CSV to the file.
     */
    private function commit(): void
    {
        $handle = fopen($this->filePath, 'rb+');

        foreach ($this->contents as $line) {
            fputcsv($handle, $line);
        }

        fclose($handle);
    }
}
