<?php

namespace Tests;

use CSVTool\CSVFile;
use CSVTool\InMemoryCSVFile;

class InMemoryCSVFileTest extends CSVFileTestCase
{
    public function testAppendingContents(): void
    {
        $this->markTestSkipped('Appending too large of a file will run out of memory.');

        $largeFile = $this->loadLargeCSVFileFromStubs();

        $totalRowCount = $largeFile->totalRows() + $this->csvFile->totalRows();

        $largeFile->append($this->csvFile);

        $reconciledRowCount = $largeFile->totalRows();

        $this->assertLessThan($totalRowCount, $reconciledRowCount);
    }

    protected function loadCSVFileFromStubs(string $fileName): CSVFile
    {
        return new InMemoryCSVFile(__DIR__ . '/' . $fileName);
    }
}
