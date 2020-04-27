<?php

namespace CSVTool;

abstract class Command
{
    abstract public function test($arguments): bool;

    abstract public function getHelp(): array;

    abstract public function run(array $arguments, array &$output): bool;

    protected function resolvePath($filePath)
    {
        return strpos($filePath, '/') === 0
            ? $filePath
            : (dirname(__DIR__) . '/' . ltrim($filePath, '/'));
    }

    protected function print(&$output, $line = '', ...$args): void
    {
        if (count($args) > 0) {
            $line = sprintf($line, ...$args);
        }

        $output[] = $line;
    }

    protected function println(&$output, $line = '', ...$args): void
    {
        $this->print($output, $line, ...$args);
        $this->print($output, "\n");
    }

    protected function assertArgumentCount($count, $arguments): void
    {
        if (count($arguments) !== $count) {
            throw new \RuntimeException(sprintf(
                '%s::run() was called with an incorrect amount of arguments. Expected %d, got %d.',
                __CLASS__,
                count($arguments),
                $count
            ));
        }
    }

    /**
     * @param array $arguments
     */
    protected function assertTwoArguments(array $arguments): void
    {
        $this->assertArgumentCount(2, $arguments);
    }

    /**
     * @param array $arguments
     */
    protected function assertThreeArguments(array $arguments): void
    {
        $this->assertArgumentCount(3, $arguments);
    }

    /**
     * @param array $arguments
     */
    protected function assertFourArguments(array $arguments): void
    {
        $this->assertArgumentCount(4, $arguments);
    }
}
