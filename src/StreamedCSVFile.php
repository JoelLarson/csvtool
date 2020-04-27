<?php

namespace CSVTool;

use Generator;
use OutOfBoundsException;
use SplFileObject;
use SplQueue;

/**
 * Class StreamedCSVFile
 * @package CSVTool
 */
class StreamedCSVFile implements CSVFile
{
    /**
     * @var string
     */
    protected $fileName;

    /**
     * StreamedCSVFile constructor.
     * @param string $fileName
     */
    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return int
     */
    public function totalRows(): int
    {
        $file = new SplFileObject($this->fileName);

        $total = 0;

        while ($file->valid()) {
            $line = $file->fgets();
            if (trim($line) !== '') {
                $total++;
            }
        }

        return $total - 1;
    }

    /**
     * @return int
     */
    public function totalColumns(): int
    {
        $fileHandle = $this->openFileForReading($this->fileName);

        $columns = fgetcsv($fileHandle);

        fclose($fileHandle);

        return count($columns);
    }

    /**
     * @param string $columnName
     * @param string $value
     */
    public function removeRowsByColumn(string $columnName, string $value): void
    {
        $columns = array_flip($this->columns());

        $columnIndex = $columns[$columnName];

        $generator = $this->manipulateRows();

        while ($generator->valid()) {
            $csvArray = $generator->current();

            $csvArray[$columnIndex] === $value
                ? $generator->next()
                : $generator->send($csvArray);
        }
    }

    /**
     * @param array $elements
     */
    public function addRow(array $elements): void
    {
        $fileHandle = $this->openFileForWritingAtEnd($this->fileName);

        fputcsv($fileHandle, $this->convertToCSVRow($elements));

        fclose($fileHandle);
    }

    /**
     * @param string $columnName
     * @param string $columnValue
     * @return array
     */
    public function filterByColumn(string $columnName, string $columnValue): array
    {
        $columns = array_flip($this->columns());

        $columnIndex = $columns[$columnName];

        $rows = [];

        $generator = $this->rows();

        while ($generator->valid()) {
            $row = $generator->current();

            if ($row[$columnIndex] === $columnValue) {
                $rows[] = $row;
            }

            $generator->next();
        }

        return $rows;
    }

    /**
     * @param int $rowIndex
     * @return array
     */
    public function retrieveRow(int $rowIndex): array
    {
        $rowGenerator = $this->rows();

        while ($rowGenerator->valid()) {
            if ($rowGenerator->key() === $rowIndex) {
                return $rowGenerator->current();
            }

            $rowGenerator->next();
        }

        throw new OutOfBoundsException('The retrieved row is out of bounds.');
    }

    /**
     * @param int $startingRowIndex
     * @return Generator
     */
    public function manipulateRows($startingRowIndex = 0): Generator
    {
        $generator = $this->manipulateFile($startingRowIndex + 1);

        while ($generator->valid()) {
            $csvArray = (yield ($generator->key() - 1) => $generator->current());

            $generator->send($csvArray);
        }
    }

    /**
     * @param int $startingIndex
     * @return Generator
     */
    public function manipulateFile($startingIndex = 0): Generator
    {
        $fileHandle = $this->openFileForOverwriting($this->fileName);

        if (flock($fileHandle, LOCK_EX)) {
            $lineNumber = 0;
            $writePosition = 0;

            $readBuffer = new SplQueue();

            while (($lineNumber < $startingIndex) && (($line = fgets($fileHandle)) !== false)) {
                $writePosition += strlen($line);
                $lineNumber++;
            }

            while (($line = fgets($fileHandle)) !== false) {
                $row = (yield $lineNumber => str_getcsv($line));

                $lineNumber++;

                if (is_array($row)) {
                    $readBuffer->enqueue($row);
                } else {
                    continue;
                }

                $readBuffer->rewind();

                $lineLength = $this->computeCSVRowLength(
                    $readBuffer->current()
                );

                $readPosition = ftell($fileHandle);

                if (($writePosition + $lineLength) >= $readPosition) {
                    continue;
                }

                $lineToWrite = $readBuffer->dequeue();

                fseek($fileHandle, $writePosition);

                $lineLength = fputcsv($fileHandle, $lineToWrite);

                $writePosition += $lineLength;
                fseek($fileHandle, $readPosition);
            }

            fseek($fileHandle, $writePosition);

            while (!$readBuffer->isEmpty()) {
                $lineToWrite = $readBuffer->dequeue();

                $lineLength = fputcsv($fileHandle, $lineToWrite);

                $writePosition += $lineLength;
            }

            fflush($fileHandle);

            ftruncate($fileHandle, $writePosition);

            flock($fileHandle, LOCK_UN);
        }

        fclose($fileHandle);
    }

    /**
     * @param int $rowIndex
     * @param array $row
     */
    public function replaceRow(int $rowIndex, array $row): void
    {
        $generator = $this->manipulateRows($rowIndex);

        while ($generator->valid()) {
            $generator->send(
                $generator->key() === $rowIndex
                    ? $this->convertToCSVRow($row)
                    : $generator->current()
            );
        }
    }

    public function updateRow(int $rowIndex, array $partialElements): void
    {
        $this->assertValidRowIndex($rowIndex);

        $columnIndexes = array_flip($this->columns());

        foreach (array_keys($partialElements) as $partialColumn) {
            if (!array_key_exists($partialColumn, $columnIndexes)) {
                throw new OutOfBoundsException('The partial elements array contains an invalid column name');
            }
        }

        $generator = $this->manipulateRows($rowIndex);

        while ($generator->valid()) {
            $row = $generator->current();

            if ($generator->key() === $rowIndex) {
                foreach ($partialElements as $columnName => $columnValue) {
                    $row[$columnIndexes[$columnName]] = $columnValue;
                }
            }

            $generator->send($row);
        }
    }

    public function append(CSVFile $csvFile): array
    {
        [];
    }

    public function columns()
    {
        $fileHandle = $this->openFileForReading($this->fileName);

        $columns = fgetcsv($fileHandle);

        fclose($fileHandle);

        return $columns;
    }

    private function rows(): Generator
    {
        $fileHandle = $this->openFileForReading($this->fileName);

        $rowIndex = 0;

        while (($line = fgetcsv($fileHandle)) !== false) {
            if ($rowIndex === 0) {
                $rowIndex++;
                continue;
            }

            yield ($rowIndex - 1) => $line;

            $rowIndex++;
        }

        fclose($fileHandle);
    }

    private function convertToCSVRow($elements): array
    {
        $keys = array_keys($elements);

        $totalNumericKeys = array_reduce($keys, static function ($key) {
            return is_numeric($key) ? 1 : 0;
        }, 0);

        $columns = $this->columns();

        if ($totalNumericKeys === $columns) {
            return $elements;
        }

        return array_map(
            static function ($columnName) use ($elements) {
                return array_key_exists($columnName, $elements) ? $elements[$columnName] : '';
            },
            $columns
        );
    }

    /**
     * @param  $filename
     * @return false|resource
     */
    private function openFileForReading($filename)
    {
        return fopen($filename, 'rb');
    }

    private function openFileForOverwriting($filename)
    {
        return fopen($filename, 'cb+');
    }

    /**
     * @return false|resource
     */
    private function openFileForWritingAtEnd($filename)
    {
        return fopen($filename, 'ab');
    }

    /**
     * @param array $row
     * @return int
     */
    private function computeCSVRowLength(array $row): int
    {
        $rowBuffer = fopen('php://temp', 'rb+');
        fputcsv($rowBuffer, $row);
        rewind($rowBuffer);
        $rowBufferText = fgets($rowBuffer);
        return strlen($rowBufferText);
    }

    /**
     * @param int $rowIndex
     */
    private function assertValidRowIndex(int $rowIndex): void
    {
        if ($rowIndex >= 0 && $rowIndex < $this->totalRows()) {
            return;
        }

        throw new OutOfBoundsException('The retrieved row is out of bounds.');
    }
}
