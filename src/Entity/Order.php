<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Dto\Order\OrderAddLineInput;
use App\Dto\Order\OrderDetailsOutput;
use App\Dto\Order\OrderPayInput;
use App\Dto\Order\OrderPayOutput;
use App\Entity\Enum\OrderStatus;
use App\Entity\Impl\AbstractEntity;
use App\Repository\OrderRepository;
use App\State\Order\OrderAddLineProcessor;
use App\State\Order\OrderCollectionProvider;
use App\State\Order\OrderOpenProcessor;
use App\State\Order\OrderPayProcessor;
use App\State\Order\OrderProvider;
use App\State\Order\OrderRemoveLineProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;

#[ApiResource(operations: [
    new Post(
        uriTemplate: '/orders',
        // le contrat n'envoie aucun corps sur cette adresse : il n'y a rien à désérialiser
        security: "is_granted('ROLE_USER')",
        input: false,
        output: OrderDetailsOutput::class,
        processor: OrderOpenProcessor::class,
    ),
    new GetCollection(
        uriTemplate: '/orders',
        paginationClientEnabled: false,
        security: "is_granted('ROLE_USER')",
        output: OrderDetailsOutput::class,
        provider: OrderCollectionProvider::class,
    ),
    new Post(
        uriTemplate: '/orders/{id}/lines',
        // un identifiant mal formé ne trouve aucune route : 404 dès le routeur, avant toute conversion
        requirements: ['id' => Requirement::UUID],
        security: "object.getCreatedBy() == user",
        input: OrderAddLineInput::class,
        output: OrderDetailsOutput::class,
        processor: OrderAddLineProcessor::class,
    ),
    new Delete(
        uriTemplate: '/orders/{id}/lines/{lineId}',
        requirements: ['id' => Requirement::UUID, 'lineId' => Requirement::UUID],
        // ni input: ni output: — le contrat n'envoie aucun corps sur cette adresse et n'en
        // attend aucun ; le 204 vient du null que rend le processor
        security: "object.getCreatedBy() == user",
        provider: OrderProvider::class,
        processor: OrderRemoveLineProcessor::class,
    ),
    new Post(
        uriTemplate: '/orders/{id}/pay',
        requirements: ['id' => Requirement::UUID],
        status: 200,
        security: "object.getCreatedBy() == user",
        input: OrderPayInput::class,
        output: OrderPayOutput::class,
        provider: OrderProvider::class,
        // le contrat déclare 200 : on ne crée pas de ressource adressable, on règle une commande
        processor: OrderPayProcessor::class,
    ),
])]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
// `order` est un mot réservé du SQL : le nom de table se protège par des accents graves
#[ORM\Table(name: '`order`')]
class Order extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(enumType: OrderStatus::class)]
    private OrderStatus $status = OrderStatus::Pending;

    /**
     * @var Collection<int, OrderLine>
     */
    #[ORM\OneToMany(
        targetEntity: OrderLine::class,
        mappedBy: 'order',
        cascade: ['persist'],
        orphanRemoval: false,
    )]
    private Collection $lines;

    /**
     * Construit une commande vide, en attente, avec un UUID ordonné dans le temps généré par l'entité.
     */
    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->lines = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, OrderLine>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    /**
     * Renvoie les lignes non supprimées de cette commande, en excluant aussi celles retirées
     * pendant la requête en cours.
     *
     * @return Collection<int, OrderLine>
     */
    public function getAliveLines(): Collection
    {
        return $this->lines->filter(
            static fn (OrderLine $line): bool => null === $line->getDeletedAt(),
        );
    }

    public function addLine(OrderLine $line): static
    {
        $this->lines->add($line);
        $line->setOrder($this);

        return $this;
    }
}
