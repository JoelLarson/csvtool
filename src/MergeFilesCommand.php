<?php

namespace CSVTool;

class MergeFilesCommand extends Command
{
    public function test($arguments): bool
    {
        if (count($arguments) !== 4) {
            return false;
        }

        $command = array_shift($arguments);
        //check if is writable
        $destination = array_shift($arguments);

        if ($command !== 'merge') {
            return false;
        }

        foreach ($arguments as $source) {
            if (! is_file($this->resolvePath($source))) {
                return false;
            }
        }

        return true;
    }

    public function getHelp(): array
    {
        return ['merge <destination> <source> <source>', 'Merge the source files to the destination.'];
    }

    public function run(array $arguments, array &$output): bool
    {
        return true;
    }
}
