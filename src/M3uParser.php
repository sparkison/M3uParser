<?php

declare(strict_types=1);

namespace M3uParser;

use Generator;

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
        $lines = \explode("\n", $str);
        for ($i = 0, $l = \count($lines); $i < $l; ++$i) {
            $lineStr = \trim($lines[$i]);
            if ('' === $lineStr || $this->isComment($lineStr)) {
                continue;
            }

            if ($this->isExtM3u($lineStr)) {
                continue;
            }

            yield $this->parseLine($i, $lines);
        };
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
     * @param string[] $linesStr
     */
    protected function parseLine(int &$lineNumber, array $linesStr): M3uEntry
    {
        $entry = $this->createM3uEntry();

        for ($l = \count($linesStr); $lineNumber < $l; ++$lineNumber) {
            $nextLineStr = $linesStr[$lineNumber];
            $nextLineStr = \trim($nextLineStr);

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
        }

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
