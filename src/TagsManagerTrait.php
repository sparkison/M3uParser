<?php

declare(strict_types=1);

namespace M3uParser;

use M3uParser\Tag\ExtAlbumArtUrl;
use M3uParser\Tag\ExtGrp;
use M3uParser\Tag\ExtInf;
use M3uParser\Tag\KodiDrop;
use M3uParser\Tag\ExtLogo;
use M3uParser\Tag\ExtTagInterface;
use M3uParser\Tag\ExtTitle;
use M3uParser\Tag\ExtTv;
use M3uParser\Tag\ExtVlcOpt;
use M3uParser\Tag\Playlist;

trait TagsManagerTrait
{
    /**
     * @var class-string<ExtTagInterface>[]
     */
    private array $tags = [];
    
    /**
     * Store parse errors encountered during parsing. Each entry is a string describing the error.
     * This can be used for logging or debugging purposes after parsing is complete.
     */
    private array $parseErrors = [];

    /**
     * Add tag.
     *
     * @param class-string<ExtTagInterface> $tag class name. Must implements the ExtTagInterface interface
     */
    public function addTag(string $tag): self
    {
        $implements = @\class_implements($tag);
        if (false === $implements) {
            throw new Exception(\sprintf('Unknown class %s', $tag));
        }
        if (!\in_array(ExtTagInterface::class, $implements, true)) {
            throw new Exception(\sprintf('The class %s must implements the %s interface', $tag, ExtTagInterface::class));
        }

        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Add default tags (EXTINF, EXTTV, EXTLOGO, EXTVLCOPT, KODIDROP, EXTGRP, PLAYLIST, EXTTITLE, EXTALBUMARTURL).
     */
    public function addDefaultTags(): self
    {
        $this->addTag(ExtInf::class);
        $this->addTag(ExtTv::class);
        $this->addTag(ExtLogo::class);
        $this->addTag(ExtVlcOpt::class);
        $this->addTag(KodiDrop::class);
        $this->addTag(ExtGrp::class);
        $this->addTag(Playlist::class);
        $this->addTag(ExtTitle::class);
        $this->addTag(ExtAlbumArtUrl::class);

        return $this;
    }

    /**
     * Remove all previously defined tags.
     */
    public function clearTags(): self
    {
        $this->tags = [];

        return $this;
    }

    /**
     * Get all active tags.
     *
     * @return class-string<ExtTagInterface>[]
     */
    protected function getTags(): array
    {
        return $this->tags;
    }
}
