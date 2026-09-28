<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Agency;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Invoice> */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'agency_id' => Agency::factory(),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
            'status' => InvoiceStatus::Draft->value,
            'due_date' => now()->addDays(30),
            'total_amount' => fake()->randomFloat(2, 250, 3500),
        ];
    }
}
