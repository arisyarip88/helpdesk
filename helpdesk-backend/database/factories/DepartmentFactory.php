<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Daftar nama divisi/departemen realistis
        $departments = [
            'IT Support', 'Software Engineering', 'Human Resources', 'Finance & Accounting',
            'Marketing & Communication', 'Sales', 'Customer Support', 'Operations',
            'Product Management', 'Quality Assurance', 'Legal & Compliance', 'Research & Development',
            'Procurement', 'Business Development', 'Creative & Design', 'Data & Analytics',
            'Logistics & Supply Chain', 'Administration', 'Security & Risk Management', 'Public Relations'
        ];

        return [
            // Mengombinasikan nama divisi dengan angka/lokasi agar unik jika lebih dari 20 record
            'nama' => fake()->randomElement($departments) . ' ' . fake()->city(),
            'ketua' => fake('id_ID')->name(), // Menggunakan nama orang Indonesia
            'deskripsi' => fake()->sentence(10), // Generasi kalimat deskripsi
        ];
    }
}