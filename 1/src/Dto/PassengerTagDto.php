<?php

namespace CardPrinterService\Dto;

class PassengerTagDto
{
    public string $id;
    public string $token;
    public string $microserviceId;

    public function getId(): string
    {
        return $this->id;
    }

    public function getMicroserviceId(): string
    {
        return $this->microserviceId;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
