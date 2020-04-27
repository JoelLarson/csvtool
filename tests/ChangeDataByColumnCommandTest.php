<?php

namespace Tests;

use CSVTool\ChangeDataByColumnCommand;
use CSVTool\Command;
use PHPUnit\Framework\TestCase;

class ChangeDataByColumnCommandTest extends TestCase
{
    /**
     * @var ChangeDataByColumnCommand
     */
    private $command;

    public function setUp(): void
    {
        parent::setUp();

        $this->command = new ChangeDataByColumnCommand();
    }

    public function testHelp(): void
    {
        $this->assertCommandHasHelp($this->command);
    }

    public function testCommandRequiresFourArguments(): void
    {
        $this->assertFalse($this->command->test([]));
        $this->assertFalse($this->command->test(['modify']));
        $this->assertFalse($this->command->test(['modify', 'Phone']));
        $this->assertFalse($this->command->test(['modify', 'Phone', '5555555555']));
        $this->assertTrue($this->command->test(['modify', 'Phone', '5555555555', $this->getTestCSVFilePath()]));
        $this->assertFalse($this->command->test(['modify', 'Phone', '5555555555', $this->getTestCSVFilePath(), 'extra']));
    }

    public function testCommandRequiresModifyArgument(): void
    {
        $this->assertFalse($this->command->test(['not_modify', 'Phone', '8991347463', $this->getTestCSVFilePath()]));
        $this->assertTrue($this->command->test(['modify', 'Phone', '8991347463', $this->getTestCSVFilePath()]));
    }

    public function testCommandRequiresValidCSV(): void
    {
        $this->assertFalse($this->command->test([
            'modify',
            'column',
            'value',
            'not_a_real_file.csv',
        ]));

        $this->assertFalse($this->command->test([
            'modify',
            'column',
            'value',
            __FILE__,
        ]));

        $this->assertTrue($this->command->test([
            'modify',
            'Phone',
            '8991347463',
            $this->getTestCSVFilePath(),
        ]));
    }

    public function testCommandRunningRequiresFourArguments(): void
    {
        $output = [];

        $this->expectException(\RuntimeException::class);
        $this->command->run([], $output);
    }

    public function testCommandChecksForInvalidColumn(): void
    {
        $output = [];

        $this->assertFalse(
            $this->command->run(['modify', 'FakePhone', '5555555555', $this->getTestCSVFilePath()], $output)
        );
    }


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
