<?php

namespace Tests;

use CSVTool\CSVFile;
use CSVTool\InMemoryCSVFile;
use PHPUnit\Framework\TestCase;

abstract class CSVFileTestCase extends TestCase
{
    protected $csvFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->copyFixture('bigtestlist1k_extra_data.csv');
        $this->copyFixture('bigtestlist500k_extra_data.csv');

        $this->csvFile = $this->loadSmallCSVFileFromStubs();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteFixture('bigtestlist1k_extra_data.csv');
        $this->deleteFixture('bigtestlist500k_extra_data.csv');
    }

    private function copyFixture($filename): void
    {
        $path = __DIR__ . '/../vendor/broadnetengineering/textfiles/data/';

        copy(realpath($path . $filename), realpath(__DIR__) . '/' . $filename);
    }

    private function deleteFixture($filename): void
    {
        unlink(realpath(__DIR__ . '/' . $filename));
    }

    public function testCountRows(): void
    {
        $this->assertEquals(1000, $this->csvFile->totalRows());
    }

    public function testCountColumns(): void
    {
        $this->assertEquals(15, $this->csvFile->totalColumns());
    }

    public function testRemovingRowsByColumn(): void
    {
        $this->csvFile->removeRowsByColumn('Phone', '8991048608');

        $this->assertEquals(999, $this->csvFile->totalRows());
    }

    public function testAddingRow(): void
    {
        $elements = $this->buildExampleRecord();

        $this->csvFile->addRow($elements);

        $rows = $this->csvFile->filterByColumn('Phone', '5555555555');

        $this->assertCount(1, $rows);
        $this->assertEquals(array_values($elements), current($rows));
    }

    public function testRetrievingOutOfRangeRow(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->csvFile->retrieveRow(-1);

        $this->expectException(\OutOfBoundsException::class);
        $this->csvFile->retrieveRow($this->csvFile->totalRows() + 1);
    }

    public function testReplacingRow(): void
    {
        $elements = $this->buildExampleRecord();

        $this->csvFile->replaceRow(0, $elements);

        $this->assertEquals(array_values($elements), $this->csvFile->retrieveRow(0));
    }

    public function testUpdatingInvalidRow(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $this->csvFile->updateRow($this->csvFile->totalRows() + 1, []);
    }

    public function testUpdatingRowWithInvalidColumn(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $this->csvFile->updateRow(0, ['Invalid Column' => 'invalid']);
    }

    public function testUpdatingRow(): void
    {
        $elements = [
            'Phone' => '5555555555',
            'Last Name' => 'John',
            'First Name' => 'Smith',
            'Title' => 'Mr.',
        ];

        $this->csvFile->updateRow(0, $elements);

        $updatedRow = $this->csvFile->retrieveRow(0);

        $this->assertEquals($elements['Phone'], $updatedRow[0]);
        $this->assertEquals($elements['Last Name'], $updatedRow[1]);
        $this->assertEquals($elements['First Name'], $updatedRow[2]);
        $this->assertEquals($elements['Title'], $updatedRow[3]);
    }

    /**
     * @return string[]
     */
    private function buildExampleRecord(): array
    {
        return [
            'Phone' => '5555555555',
            'Last Name' => 'John',
            'First Name' => 'Smith',
            'Title' => 'Mr.',
            'Address' => '6962 Benton Corner',
            'Address 2' => 'Apt. 465',
            'City' => 'South Greenborough',
            'State' => 'KS',
            'Zip Code' => '20340',
            'Job Title' => 'Engineer',
            'Email' => 'johnsmith@gmail.com',
            'Voted' => 'Y',
            'District' => '2',
            'Special ID' => '9.95E+12',
            'Party' => 'D'
        ];
    }

    /**
     * @param  string $fileName
     * @return CSVFile
     */
    protected function loadCSVFileFromStubs(string $fileName): CSVFile
    {
        return new InMemoryCSVFile(sprintf(__DIR__ . '/%s', $fileName));
    }

    protected function loadSmallCSVFileFromStubs(): CSVFile
    {
        return $this->loadCSVFileFromStubs('bigtestlist1k_extra_data.csv');
    }

    protected function loadLargeCSVFileFromStubs(): CSVFile
    {
        return $this->loadCSVFileFromStubs('bigtestlist500k_extra_data.csv');
    }
}
