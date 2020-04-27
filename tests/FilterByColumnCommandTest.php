<?php

namespace Tests;

use CSVTool\Command;
use CSVTool\FilterByColumnCommand;
use PHPUnit\Framework\TestCase;

class FilterByColumnCommandTest extends TestCase
{
    /**
     * @var FilterByColumnCommand
     */
    private $command;

    public function setUp(): void
    {
        parent::setUp();

        $this->command = new FilterByColumnCommand();
    }

    public function testHelp(): void
    {
        $this->assertCommandHasHelp($this->command);
    }

    public function testCommandRequiresFourArguments(): void
    {
        $this->assertFalse($this->command->test([]));
        $this->assertFalse($this->command->test(['filter']));
        $this->assertFalse($this->command->test(['filter', 'column']));
        $this->assertFalse($this->command->test(['filter', 'column', 'value']));
        $this->assertFalse($this->command->test(['filter', 'column', 'value']));

        $this->assertTrue($this->command->test(['filter', 'column', 'value', $this->getTestCSVFilePath()]));

        $this->assertFalse($this->command->test(['filter', 'column', 'value', $this->getTestCSVFilePath(), 'extra']));
    }

    public function testCommandRequiresFilterArgument(): void
    {
        $this->assertFalse($this->command->test(['not_filter', 'column', 'value', 'filepath']));
        $this->assertTrue($this->command->test(['filter', 'column', 'value', $this->getTestCSVFilePath()]));
    }

    public function testCommandRequiresAValidCSV()
    {
        $this->assertFalse($this->command->test([
            'filter',
            'column',
            'value',
            'not_a_real_file.csv',
        ]));

        $this->assertFalse($this->command->test([
            'filter',
            'column',
            'value',
            __FILE__,
        ]));

        $this->assertTrue($this->command->test([
            'filter',
            'column',
            'value',
            $this->getTestCSVFilePath(),
        ]));
    }

    public function testCommandRequiresEnoughArguments(): void
    {
        $output = [];

        $this->expectException(\RuntimeException::class);
        $this->command->run([], $output);
    }

    public function testCommandChecksForInvalidColumn(): void
    {
        $output = [];

        $this->assertFalse(
            $this->command->run(['filter', 'FakePhone', '5555555555', $this->getTestCSVFilePath()], $output)
        );
    }

    public function testCommandFiltersColumnByValue(): void
    {
        $output = [];

        $this->assertTrue(
            $this->command->run(['filter', 'Phone', '8991287004', $this->getTestCSVFilePath()], $output)
        );
    }

    // add test for checking valid columns

    protected function assertCommandHasHelp(Command $command): void
    {
        $help = $command->getHelp();

        $this->assertCount(2, $help);
        $this->assertIsString($help[0]);
        $this->assertIsString($help[1]);
    }

    /**
     * @return string
     */
    private function getTestCSVFilePath(): string
    {
        return dirname(__DIR__) . '/vendor/broadnetengineering/textfiles/data/bigtestlist1k_extra_data.csv';
    }
}
