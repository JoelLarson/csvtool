<?php

namespace CSVTool;

class AddCommand extends Command
{
    public function test($arguments): bool
    {
        if (count($arguments) !== 2) {
            return false;
        }

        if ($arguments[0] !== 'add') {
            return false;
        }

        if (strstr($arguments[1], '/') === 0) {
            return is_file($arguments[1]);
        }

        return realpath(dirname(__DIR__) . '/' . ltrim($arguments[1], '/'));
    }

    public function getHelp(): array
    {
        return ['add <file>', 'Add a new entry to the end of the CSV file.'];
    }

    public function run(array $arguments, array &$output): bool
    {
        $this->assertTwoArguments($arguments);

        $path = $arguments[0];

        $csvFile = new StreamedCSVFile($path);

        $columns = $csvFile->columns();

        $totalColumns = count($columns);

        $this->println($output, 'Adding a new entry to \'%s\'', basename($path));

        $inputtedData = [];

        foreach ($columns as $index => $column) {
            $this->print($output, "(%d/%d) %s: ", $index + 1, $totalColumns, $column);
            $line = rtrim(fgets(STDIN));

            $inputtedData[$index] = $line;
        }

        $csvFile->addRow($inputtedData);

        var_dump($inputtedData);

        $this->println($output, '----------');

        return true;
    }
}
