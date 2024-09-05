<?php

namespace CardPrinterService\Service;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class FontPathProvider
{
    public function __construct(private readonly string $publicDir)
    {
    }

    public function getPath(string $fontName, string $style = 'NORMAL'): string
    {
        if ($style !== 'NORMAL') {
            $fontPath = sprintf('%s/fonts/%s%s.ttf', $this->publicDir, $fontName, ucwords(strtolower($style)));
        } else {
            $fontPath = sprintf('%s/fonts/%s.ttf', $this->publicDir, $fontName);
        }

        if (!file_exists($fontPath)) {
            throw new FileNotFoundException(sprintf('Font file `%s` not found', $fontPath));
        }

        return $fontPath;
    }
}
