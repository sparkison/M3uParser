<?php

declare(strict_types=1);

namespace M3uParser;

use Generator;
use SplFileObject;

class M3uParser
{
    use TagsManagerTrait;

    /**
     * Parse m3u file.
     */
    public function parseFile(string $file): Generator
    {
        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $file);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

        $str = $output;
        if (false === $str) {
            throw new Exception('Can\'t read file.');
        }

        return $this->parse($str);
    }

    /**
     * Parse m3u string.
     */
    public function parse(string $str): Generator
    {
        $this->removeBom($str);
        return $this->createGenerator($str);
    }

    protected function createGenerator($str): Generator
    {
        // open a temporary file handle in memory
        $handle = fopen('php://temp', 'r+');

        // write the string to the file handle
        fwrite($handle, $str);

        // rewind the file handle to the beginning
        rewind($handle);

        // parse the file line by line
        while (($line = \fgets($handle, 2048)) !== false) {
            $lineStr = \rtrim($line, "\n\r");
            if ('' === $lineStr || $this->isComment($lineStr)) {
                continue;
            }

            if ($this->isExtM3u($lineStr)) {
                continue;
            }

            yield $this->parseLine($line, $handle);
        }

        if (!feof($handle)) {
            throw new Exception('Error while reading file.');
        }

        // close the file handle
        fclose($handle);
    }

    protected function createM3uEntry(): M3uEntry
    {
        return new M3uEntry();
    }

    protected function createM3uData(): M3uData
    {
        return new M3uData();
    }

    /**
     * Parse one line.
     *
     * @param string $line
     * @param resource $handle
     */
    protected function parseLine(string $line, $handle): M3uEntry
    {
        $entry = $this->createM3uEntry();
        $nextLineStr = $line;
        do {
            $nextLineStr = \rtrim($nextLineStr, "\n\r");

            if ('' === $nextLineStr || $this->isComment($nextLineStr) || $this->isExtM3u($nextLineStr)) {
                continue;
            }

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
        } while ((($nextLineStr = \fgets($handle, 2048)) !== false));

        return $entry;
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
