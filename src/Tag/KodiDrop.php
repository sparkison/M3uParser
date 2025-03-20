<?php

declare(strict_types=1);

namespace M3uParser\Tag;

class KodiDrop implements ExtTagInterface
{
    private string $key;
    private string $value;

    /**
     * #KODIPROP:inputstream.adaptive.license_key=https://proxy.drm.pbs.org/license/widevine/
     */
    public function __construct(?string $lineStr = null)
    {
        if (null !== $lineStr) {
            $this->make($lineStr);
        }
    }

    public function __toString(): string
    {
        return '#KODIPROP:'.$this->getKey().'='.$this->getValue();
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function isMatch(string $lineStr): bool
    {
        return 0 === \stripos($lineStr, '#KODIPROP:');
    }

    protected function make(string $lineStr): void
    {
        /*
KODIPROP format:
#KODIPROP:<key> = <value>
example:
#KODIPROP:inputstream.adaptive.license_key=https://proxy.drm.pbs.org/license/widevine/
         */
        $dataLineStr = \substr($lineStr, \strlen('#KODIPROP:'));
        $dataLineStr = \trim($dataLineStr);

        [$key, $value] = \explode('=', $dataLineStr, 2);

        $this->setKey($key);
        $this->setValue($value);
    }
}
