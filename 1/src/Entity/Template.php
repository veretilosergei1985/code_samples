<?php

namespace CardPrinterService\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use CardPrinterService\Controller\GetPrintableCardAction;
use CardPrinterService\Controller\ToggleTemplateAction;
use CardPrinterService\Repository\TemplateRepository;
use CardPrinterService\Shared\Trait\TimestampableEntityTrait;
use CardPrinterService\Validator as CustomAssert;
use CardPrinterService\Validator as TemplateAssert;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TemplateRepository::class)]
#[TemplateAssert\UniqEnabledTypedCustomerTemplateConstraint]
#[ApiResource(
    normalizationContext: [
        'groups' => [
            'getTemplate',
        ],
    ],
    denormalizationContext: [
        'groups' => [
            'setTemplate',
        ],
    ],
    paginationItemsPerPage: 20
)]
#[ApiFilter(SearchFilter::class, properties: [
    'customer' => 'exact',
    'isEnabled' => 'exact',
    'product' => 'exact',
    'type' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: [
    'name',
    'isEnabled',
    'type',
    'product',
    'createdAt',
    'updatedAt',
    ],
    arguments: ['orderParameterName' => 'order']
)]
#[Get(security: "is_granted('ROLE_CARD_PRINTER_READER')", validationContext: ['groups' => ['Default', 'readTemplate']])]
#[Get(
    uriTemplate: '/printable_card/passengers/{passengerId}/templates/{templateId}',
    controller: GetPrintableCardAction::class,
    openapiContext: [
        'summary' => 'Get a printable passenger card',
        'responses' => [
            '200' => [
                'content' => [
                    'image/jpg' => [
                        'schema' => [
                            'type' => 'string',
                            'format' => 'binary',
                        ],
                    ],
                ],
            ],
        ],
    ],
    read: false,
    name: 'printable_card'
)]
#[Get(
    uriTemplate: '/templates/{id}/toggle',
    controller: ToggleTemplateAction::class,
    openapiContext: [
        'summary' => 'Toggle template status',
    ],
    security: "is_granted('ROLE_CARD_PRINTER_WRITER')",
    read: false,
    name: 'toggle'
)]
#[GetCollection(security: "is_granted('ROLE_CARD_PRINTER_READER')")]
#[Put(security: "is_granted('ROLE_CARD_PRINTER_WRITER')")]
#[Patch(security: "is_granted('ROLE_CARD_PRINTER_WRITER')")]
#[Post(security: "is_granted('ROLE_CARD_PRINTER_WRITER')")]
#[Delete(security: "is_granted('ROLE_CARD_PRINTER_WRITER')")]
class Template
{
    use TimestampableEntityTrait;

    public const TYPES = ['NOMINATIVE', 'ANONYMOUS', 'DECLARATIVE'];
    public const PRODUCTS = ['2place', '2school'];

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(['getTemplate'])]
    #[Assert\NotBlank(groups: ['readTemplate'])]
    private ?UuidInterface $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'The name field can not be null')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'The name field cannot be longer than {{ limit }} characters',
    )]
    #[Assert\Type(
        type: 'string',
        message: 'The value {{ value }} is not a valid {{ type }}.',
    )]
    #[Groups(['getTemplate', 'setTemplate'])]
    private string $name;

    #[ORM\Column]
    #[Assert\NotNull(message: 'The isEnabled field can not be null')]
    #[Assert\Type(
        type: 'boolean',
        message: 'The value {{ value }} is not a valid {{ type }}.',
    )]
    #[Groups(['getTemplate', 'setTemplate'])]
    private bool $isEnabled;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['getTemplate'])]
    private ?string $customer = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'The items field can not be null')]
    #[Assert\Choice(choices: Template::TYPES, message: 'Choose a valid type.')]
    #[Assert\Type(
        type: 'string',
        message: 'The value {{ value }} is not a valid {{ type }}.',
    )]
    #[Groups(['getTemplate', 'setTemplate'])]
    private string $type;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'The product field can not be null')]
    #[Assert\Choice(choices: Template::PRODUCTS, message: 'Choose a valid product.')]
    #[Assert\Type(
        type: 'string',
        message: 'The value {{ value }} is not a valid {{ type }}.',
    )]
    #[Groups(['getTemplate', 'setTemplate'])]
    private string $product = '2place';

    #[ORM\Column]
    #[Assert\NotNull(message: 'The elements field can not be null')]
    #[CustomAssert\ElementsConstraint(message: 'The elements field should have valid format.')]
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'items' => ['type' => 'object'],
        ],
        jsonSchemaContext: [
            'type' => 'array',
            'items' => ['type' => 'object'],
        ]
    )]
    #[Groups(['getTemplate', 'setTemplate'])]
    private array $elements = [];

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getIsEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    public function getCustomer(): ?string
    {
        return $this->customer;
    }

    public function setCustomer(?string $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getProduct(): string
    {
        return $this->product;
    }

    public function setProduct(string $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getElements(): array
    {
        return $this->elements;
    }

    public function setElements(array $elements): self
    {
        $this->elements = $elements;

        return $this;
    }
}
