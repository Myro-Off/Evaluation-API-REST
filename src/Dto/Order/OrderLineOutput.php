<?php

namespace App\Dto\Order;

use ApiPlatform\Metadata\ApiProperty;
use App\Dto\Dish\DishListOutput;
use Symfony\Component\Uid\Uuid;

final readonly class OrderLineOutput
{
    public function __construct(
        #[ApiProperty(schema: [
            'type' => 'string',
            'format' => 'uuid',
            'description' => 'Identifiant de la ligne.',
        ])]
        public Uuid           $id,

        #[ApiProperty(description: 'Plat commandé.')]
        public DishListOutput $dish,

        #[ApiProperty(schema: [
            'type' => 'integer',
            'description' => 'Nombre de portions commandées.',
        ])]
        public int            $quantity,

        #[ApiProperty(schema: [
            'type' => 'integer',
            'description' => 'Prix du plat multiplié par la quantité, en centimes.',
        ])]
        public int            $subtotal,
    ) {
    }
}
