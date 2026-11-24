<?php

namespace Database\Factories;

use App\Models\Inventaris;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventaris>
 */
class InventarisFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->randomElement(['Laptop', 'Printer', 'Lemari arsip', 'Meja kerja', 'Kursi lipat', 'Brankas']),
            'jumlah' => fake()->numberBetween(1, 4),
            'satuan' => 'unit',
            'tanggal_perolehan' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'nilai' => fake()->numberBetween(5, 80) * 100000,
            'kondisi' => 'baik',
            'lokasi' => 'Ruang koperasi',
        ];
    }

    public function dihapus(): static
    {
        return $this->state(fn () => ['kondisi' => 'rusak_berat', 'tanggal_dihapus' => now()->subDays(3)->toDateString()]);
    }
}
