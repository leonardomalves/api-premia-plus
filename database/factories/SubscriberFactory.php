<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscriber>
 */
class SubscriberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $utmSources = ['google', 'facebook', 'instagram', 'youtube', 'tiktok', 'direct', 'referral'];
        $utmMediums = ['cpc', 'organic', 'social', 'email', 'referral', 'direct'];
        $campaigns = ['pre-launch', 'early-bird', 'beta-access', 'vip-list', 'black-friday'];
        
        return [
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'country' => 'BR',
            'utm_source' => $this->faker->randomElement($utmSources),
            'utm_medium' => $this->faker->randomElement($utmMediums),
            'utm_campaign' => $this->faker->randomElement($campaigns),
            'utm_term' => $this->faker->optional()->words(2, true),
            'utm_content' => $this->faker->optional()->word(),
            'referrer_url' => $this->faker->optional()->url(),
            'tracking_data' => [
                'landing_page' => '/pre-launch',
                'form_version' => $this->faker->randomElement(['A', 'B', 'C']),
                'button_clicked' => $this->faker->randomElement(['cta-hero', 'cta-footer', 'cta-popup']),
            ],
            'status' => $this->faker->randomElement(['pending', 'active', 'converted', 'unsubscribed']),
            'subscription_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'email_verified_at' => $this->faker->optional(0.7)->dateTimeBetween('-25 days', 'now'),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'device_info' => [
                'device' => $this->faker->randomElement(['mobile', 'desktop', 'tablet']),
                'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                'os' => $this->faker->randomElement(['Windows', 'macOS', 'iOS', 'Android', 'Linux']),
            ],
            'preferences' => [
                'newsletter' => $this->faker->boolean(80),
                'promotions' => $this->faker->boolean(60),
                'updates' => $this->faker->boolean(90),
            ],
        ];
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'converted',
            'converted_at' => $this->faker->dateTimeBetween('-15 days', 'now'),
            'conversion_value' => $this->faker->randomFloat(2, 50, 500),
        ]);
    }

    public function withSponsor(): static
    {
        return $this->state(fn (array $attributes) => [
            'sponsor_id' => \App\Models\User::factory(),
        ]);
    }

    public function fromSource(string $source): static
    {
        return $this->state(fn (array $attributes) => [
            'utm_source' => $source,
        ]);
    }
}
