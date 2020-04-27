<?php

namespace CSVTool;

class FilterByColumnCommand extends Command
{
    public function test($arguments): bool
    {
        if (count($arguments) !== 4) {
            return false;
        }

        [$commandName, $column, $value, $filePath] = $arguments;

        if ($commandName !== 'filter') {
            return false;
        }

        $realPath = $this->resolvePath($filePath);

        if (! is_file($realPath)) {
            return false;
        }

        return pathinfo($realPath, PATHINFO_EXTENSION) === 'csv';
    }

    public function getHelp(): array
    {
        return ['filter <column> <value> <filepath>', 'Filter the specified file by the column value.'];
    }

    public function run(array $arguments, array &$output): bool
    {
        $this->assertFourArguments($arguments);

        [$command, $columnName, $value, $filePath] = $arguments;

        $file = new StreamedCSVFile($this->resolvePath($arguments[3]));

        $columns = $file->columns();

        $matchedColumns = array_filter($columns, static function ($csvColumnName) use ($columnName) {
            return $csvColumnName === $columnName;
        });

        if (count($matchedColumns) > 1) {
            $this->println($output, "More than one '{$columnName}' was matched. Operation not completed.");
            return false;
        }

        if (count($matchedColumns) === 0) {
            $this->print($output, "The column '{$columnName} was not found in the CSV file. Operation not completed.");
            return false;
        }

        $rows = $file->filterByColumn($columnName, $value);

        $this->println($output, "Matching rows found:");
        $this->println($output);

        $this->println($output, implode(', ', $file->columns()));

        foreach ($rows as $row) {
            $this->println($output, implode(', ', $row));
        }

        return true;
    }
}
