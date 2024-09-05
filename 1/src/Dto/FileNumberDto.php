<?php

namespace CardPrinterService\Dto;

class FileNumberDto
{
    public string $fileNumber;
    public bool $automatic;

    public function getFileNumber(): string
    {
        return $this->fileNumber;
    }

    public function isAutomatic(): bool
    {
        return $this->automatic;
    }
}
