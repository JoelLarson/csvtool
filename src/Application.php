<?php

namespace CSVTool;

/**
 * Class Application
 * @package CSVTool
 */
class Application
{
    private $initialMemory;

    public function __construct($initialMemory)
    {
        $this->initialMemory = $initialMemory;
    }

    public function run($argv): void
    {
        $this->printHeader(
            'Broadnet Engineering Challenge',
            'by Joel Larson <joellarsonweb@gmail.com>'
        );

        array_shift($argv);

        $commands = [
            new ListCommand(),
            new AddCommand(),
            new FilterByColumnCommand(),
            new MergeFilesCommand(),
            new RemoveRowsByColumnCommand(),
            new ChangeDataByColumnCommand(),
        ];

        $matchedCommands = array_values(array_filter($commands, static function ($command) use ($argv) {
            return $command->test($argv);
        }));

        if (count($matchedCommands) === 1) {
            $outputBuffer = [];

            $matchedCommands[0]->run($argv, $outputBuffer);

            foreach ($outputBuffer as $line) {
                echo $line;
            }
        } else {
            $this->println('Command not found or invalid parameters.');
            $this->println();
            $this->printHelp($commands);
        }

        $this->println();
        $this->println('----------');
        $this->println();
        $this->printMemoryLine('Initial Memory', $this->initialMemory);
        $this->printMemoryLine('Peak Runtime Memory', memory_get_peak_usage());
    }

    private function printMemoryLine($label, $bytes)
    {
        $this->println("{$label}: " . $this->humanizeBytes($bytes));
    }

    private function humanizeBytes($bytes)
    {
        $unit = array('b','kb','mb','gb','tb','pb');
        return @round($bytes / (1024 ** ($i = floor(log($bytes, 1024)))), 2) . ' ' . $unit[$i];
    }

    private function printHelp($commands): void
    {
        $helpOptions = array_map(static function ($command) {
            return $command->getHelp();
        }, $commands);

        $this->println('Available commands:');
        $this->printOptions($helpOptions);
    }

    protected function printHeader(string $headerText, string $subText): void
    {
        $this->println($headerText);
        $this->println('  ' . $subText);
        $this->println();
    }

    protected function printOptions(array $options): void
    {
        $padLength = array_reduce($options, static function ($width, $option) {
            return max($width, strlen($option[0]));
        }, 0);

        $this->println();
        foreach ($options as $option) {
            $this->println('  ' . str_pad($option[0], $padLength * 1.2) . $option[1]);
        }
    }

    protected function print($line = '', ...$args): void
    {
        if (count($args) > 0) {
            $line = sprintf($line, ...$args);
        }

        echo $line;
    }

    protected function println($line = '', ...$args): void
    {
        $this->print($line, ...$args);
        echo "\n";
    }
}
