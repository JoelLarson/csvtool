<?php

namespace Tests;

use CSVTool\CSVFile;
use CSVTool\StreamedCSVFile;

class StreamedCSVFileTest extends CSVFileTestCase
{
    //    public function testAppendingContents(): void
    //    {
    //        $largeFile = $this->loadLargeCSVFileFromStubs();
    //
    //        $totalRowCount = $largeFile->totalRows() + $this->csvFile->totalRows();
    //
    //        $largeFile->append($this->csvFile);
    //
    //        $reconciledRowCount = $largeFile->totalRows();
    //
    //        $this->assertLessThan($totalRowCount, $reconciledRowCount);
    //    }

    protected function loadCSVFileFromStubs(string $fileName): CSVFile
    {
        return new StreamedCSVFile(__DIR__ . '/' . $fileName);
    }
}
