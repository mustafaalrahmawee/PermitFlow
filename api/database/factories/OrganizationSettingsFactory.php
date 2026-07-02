<?php

namespace Database\Factories;

use App\Models\OrganizationSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationSettings>
 */
class OrganizationSettingsFactory extends Factory
{
    protected $model = OrganizationSettings::class;

    public function definition(): array
    {
        return [
            'singleton_key' => 'single_organization',
            'organization_name' => fake()->company(),
            'settings_payload' => [
                'locale' => 'en',
                'timezone' => 'UTC',
            ],
            'updated_by_user_account_id' => null,
        ];
    }
}
