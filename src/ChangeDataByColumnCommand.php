<?php

namespace CSVTool;

class ChangeDataByColumnCommand extends Command
{
    public function test($arguments): bool
    {
        if (count($arguments) !== 4) {
            return false;
        }

        [$command, $column, $value, $filePath] = $arguments;

        if ($command !== 'modify') {
            return false;
        }

        $realPath = $this->resolvePath($filePath);

        if (!is_file($realPath)) {
            return false;
        }

        if (pathinfo($realPath, PATHINFO_EXTENSION) !== 'csv') {
            return false;
        }

        return true;
    }

    public function getHelp(): array
    {
        return [
            'modify <column> <value> <filepath>',
            'Modify the rows matched by column value in the specified file (warning: command saves over specified file)'
        ];
    }

    public function run(array $arguments, array &$output): bool
    {
        $this->assertFourArguments($arguments);

        [$command, $columnName, $value, $filePath] = $arguments;

        $path = $this->resolvePath($filePath);

        $file = new StreamedCSVFile($path);

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

        $invertedColumns = array_flip($columns);

        $columnIndex = $invertedColumns[$columnName];

        $generator = $file->manipulateRows();

        while ($generator->valid()) {
            $csvRow = $generator->current();

            if ($csvRow[$columnIndex] === $value) {
                echo "Row Matched: \n\n";
                echo '  ';
                echo implode(', ', $columns);
                echo "\n";
                echo '  ';
                echo implode(', ', $csvRow);
                echo "\n\n";

                foreach ($csvRow as $index => $v) {
                    echo "{$columns[$index]}: '{$v}'\n";

                    echo 'Change value (y/n)? ';
                    $changeFile = trim(fgets(STDIN));
                    echo "\n";

                    if ($changeFile === 'y') {
                        echo 'New Value: ';
                        $newValue = fgets(STDIN);
                        $csvRow[$index] = $newValue;
                        echo "\n";
                    }
                }
            }

            $generator->send($csvRow);
        }

        return true;
    }
}
