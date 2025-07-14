<?php

declare(strict_types=1);

namespace M3uParser;

use Generator;
use InvalidArgumentException;

class M3uParser
{
    use TagsManagerTrait;

    /**
     * Parse m3u file.
     * 
     * @param string $filePath
     * @param int $max_length
     * 
     * @return Generator<M3uEntry>
     */
    public function parseFile(string $filePath, int $max_length = 2048): Generator
    {
        return $this->createGenerator($filePath, $max_length);
    }

    /**
     * Create a generator.
     * 
     * @param string $filePath
     * @param int $max_length
     * 
     * @return Generator<M3uEntry>
     * @throws Exception
     */
    protected function createGenerator(string $filePath, int $max_length): Generator
    {
        // create a file handle
        $handle = fopen($filePath, 'r');

        // parse the file line by line
        $index = 0;
        while (($line = \fgets($handle, $max_length)) !== false) {
            // remove BOM
            if (0 === $index) {
                $this->removeBom($line);
            }
            $index++;

            // make sure we have a full line and not a partial line (too long/malformed)
            if (!(\str_ends_with($line, "\n") || \str_ends_with($line, "\r"))) {
                continue;
            }
            $lineStr = \rtrim($line, "\r\n");

            if ('' === $lineStr || $this->isComment($lineStr)) {
                continue;
            }

            if ($this->isExtM3u($lineStr)) {
                continue;
            }

            try {
                $output = $this->parseLine($line, $handle, $max_length);
                if (null !== $output) {
                    yield $output;
                }
            } catch (InvalidArgumentException $e) {
                // Set parse error and continue
                $this->parseErrors[] = sprintf('Line %d: %s', $index, $e->getMessage());
            }
        }

        if (!feof($handle)) {
            throw new Exception('Error while reading file.');
        }

        // close the file handle
        fclose($handle);
    }

    /**
     * Parse one line.
     *
     * @param string $line
     * @param $handle
     * @param int $max_length
     */
    protected function parseLine(string $line, $handle, int $max_length): M3uEntry
    {
        $entry = $this->createM3uEntry();
        $nextLineStr = $line;
        do {
            $nextLineStr = \rtrim($nextLineStr, "\r\n");

            if ('' === $nextLineStr || $this->isComment($nextLineStr) || $this->isExtM3u($nextLineStr)) {
                continue;
            }

            try {
                $matched = false;
                foreach ($this->getTags() as $availableTag) {
                    if ($availableTag::isMatch($nextLineStr)) {
                        $matched = true;
                        $entry->addExtTag(new $availableTag($nextLineStr));

                        break;
                    }
                }

                if (!$matched) {
                    $entry->setPath($nextLineStr);

                    break;
                }
            } catch (InvalidArgumentException $e) {
                // Just rethrow the exception, we'll handle upstream
                throw $e;
            }
        } while ((($nextLineStr = \fgets($handle, $max_length)) !== false));

        return $entry;
    }

    /*
     * Helper functions
     */
    protected function createM3uEntry(): M3uEntry
    {
        return new M3uEntry();
    }

    protected function createM3uData(): M3uData
    {
        return new M3uData();
    }

    protected function removeBom(string &$str): void
    {
        if (\str_starts_with($str, "\xEF\xBB\xBF")) {
            $str = \substr($str, 3);
        }
    }

    protected function isExtM3u(string $lineStr): bool
    {
        return 0 === \stripos($lineStr, '#EXTM3U');
    }

    protected function isComment(string $lineStr): bool
    {
        $matched = false;
        foreach ($this->getTags() as $availableTag) {
            if ($availableTag::isMatch($lineStr)) {
                $matched = true;

                break;
            }
        }

        return !$matched && \str_starts_with($lineStr, '#') && !$this->isExtM3u($lineStr);
    }
}
