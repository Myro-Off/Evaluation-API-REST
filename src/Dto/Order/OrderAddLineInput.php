<?php

namespace App\Dto\Order;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Validator\Constraints as Assert;

final class OrderAddLineInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[ApiProperty(required: true, schema: [
            'type' => 'string',
            'format' => 'uuid',
            'description' => 'Identifiant du plat à ajouter.',
        ])]
        public string $dishId,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[ApiProperty(required: true, schema: [
            'type' => 'integer',
            'description' => 'Nombre de portions du plat.',
            'minimum' => 1,
        ])]
        public int $quantity,
    ) {
    }
}
