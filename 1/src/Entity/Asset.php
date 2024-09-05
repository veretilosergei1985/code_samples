<?php

namespace CardPrinterService\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use CardPrinterService\Controller\CreateAssetAction;
use CardPrinterService\Repository\AssetRepository;
use CardPrinterService\Shared\Trait\TimestampableEntityTrait;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AssetRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['asset:read']],
    operations: [
        new Get(security: "is_granted('ROLE_CARD_PRINTER_READER')"),
        new GetCollection(security: "is_granted('ROLE_CARD_PRINTER_READER')"),
        new Post(
            controller: CreateAssetAction::class,
            security: "is_granted('ROLE_CARD_PRINTER_WRITER')",
            deserialize: false,
            validationContext: ['groups' => ['Default', 'asset:create']],
            openapiContext: [
                'requestBody' => [
                    'content' => [
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ),
    ]
)]
class Asset
{
    use TimestampableEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private UuidInterface $id;

    #[Groups(['asset:read'])]
    #[ApiProperty(required: true)]
    public string $signedUrl;

    #[Assert\NotNull(groups: ['asset:create'])]
    public ?File $file = null;

    #[ORM\Column(nullable: true)]
    public ?string $filePath = null;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): void
    {
        $this->id = $id;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }
}
