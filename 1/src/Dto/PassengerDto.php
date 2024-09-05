<?php

namespace CardPrinterService\Dto;

class PassengerDto
{
    public string $id;
    public string $microserviceId;
    public FileNumberDto $fileNumber;
    public string $firstName;
    public string $lastName;
    public string $dateOfBirth;
    public ?string $sourceType;

    public function getFileNumber(): FileNumberDto
    {
        return $this->fileNumber;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getDateOfBirth(): string
    {
        return $this->dateOfBirth;
    }

    public function getMicroserviceId(): string
    {
        return $this->microserviceId;
    }

    public function setSourceType(?string $sourceType): void
    {
        $this->sourceType = $sourceType;
    }
}
