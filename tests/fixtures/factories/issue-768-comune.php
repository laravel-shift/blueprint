<?php

namespace Database\Factories;

use App\Models\Provincia;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComuneFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'codice' => fake()->regexify('[A-Za-z0-9]{3}'),
            'descrizione' => fake()->regexify('[A-Za-z0-9]{100}'),
            'provincia_id' => Provincia::factory(),
        ];
    }
}
