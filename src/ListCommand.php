<?php

namespace CSVTool;

class ListCommand extends Command
{
    public function test($arguments): bool
    {
        if (count($arguments) !== 2) {
            return false;
        }

        if ($arguments[0] !== 'list') {
            return false;
        }

        if (strstr($arguments[1], '/') === 0) {
            return is_dir($arguments[1]);
        }

        return is_dir(dirname(__DIR__) . '/' . ltrim($arguments[1], '/'));
    }

    public function getHelp(): array
    {
        return ['list <directory>', 'List all of the files in the directory with size information.'];
    }

    public function run(array $arguments, array &$output): bool
    {
        $this->assertTwoArguments($arguments);

        $path = realpath($arguments[1]);

        $files = $this->getCSVFilesInDirectory($path);

        $this->println($output, 'Files found:');

        foreach ($files as $file) {
            $csvFile = new StreamedCSVFile("{$path}/{$file}");

            $this->println($output);
            $this->println($output, "  > {$file}");
            $this->println($output, '      Total Columns: %s', number_format($csvFile->totalColumns()));
            $this->println($output, '      Total Rows: %s', number_format($csvFile->totalRows()));
        }

        return true;
    }

    private function getCSVFilesInDirectory($directory)
    {
        $files = scandir($directory);

        return array_filter($files, static function ($fileName) {
            return pathinfo($fileName, PATHINFO_EXTENSION) === 'csv';
        });
    }
}
