<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SubscriberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Cria 500 subscribers com diferentes status:
     * - 60% pending/active (300 leads)
     * - 30% converted (150 convertidos em users)
     * - 10% unsubscribed (50 descadastrados)
     */
    public function run(): void
    {
        $this->command->info('🚀 Criando 500 subscribers para sistema de pré-lançamento...');

        DB::transaction(function () {
            // 1. Criar sponsors (usuários que podem indicar)
            $sponsors = $this->createSponsors();
            $this->command->info('✅ Criados ' . count($sponsors) . ' sponsors');

            // 2. Criar 300 leads não convertidos (pending/active)
            $this->createNonConvertedSubscribers(300);
            $this->command->info('✅ Criados 300 leads não convertidos');

            // 3. Criar 150 leads convertidos (viram usuários)
            $this->createConvertedSubscribers(150, $sponsors);
            $this->command->info('✅ Criados 150 leads convertidos em usuários');

            // 4. Criar 50 leads descadastrados
            $this->createUnsubscribedSubscribers(50);
            $this->command->info('✅ Criados 50 leads descadastrados');
        });

        $this->displayStatistics();
    }

    /**
     * Criar usuários sponsors (que podem indicar outros)
     */
    private function createSponsors(): array
    {
        $sponsors = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $sponsor = User::create([
                'uuid' => fake()->uuid(),
                'name' => fake()->name(),
                'email' => "sponsor{$i}@premiaclub.com",
                'username' => "sponsor{$i}",
                'phone' => fake()->phoneNumber(),
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'role' => 'customer',
                'status' => 'active',
            ]);
            
            $sponsors[] = $sponsor;
        }

        return $sponsors;
    }

    /**
     * Criar subscribers não convertidos (pending/active)
     */
    private function createNonConvertedSubscribers(int $count): void
    {
        $campaigns = [
            'pre-launch-facebook' => 80,
            'pre-launch-google' => 70,
            'pre-launch-instagram' => 60,
            'early-bird-email' => 40,
            'vip-list-organic' => 30,
            'referral-program' => 20,
        ];

        $created = 0;
        foreach ($campaigns as $campaign => $quantity) {
            if ($created >= $count) break;
            
            $remaining = min($quantity, $count - $created);
            
            Subscriber::factory()
                ->count($remaining)
                ->state([
                    'utm_campaign' => $campaign,
                    'status' => fake()->randomElement(['pending', 'active']),
                    'subscription_date' => fake()->dateTimeBetween('-45 days', '-1 day'),
                    'email_verified_at' => fake()->optional(0.7)->dateTimeBetween('-40 days', 'now'),
                ])
                ->create();
                
            $created += $remaining;
        }
    }

    /**
     * Criar subscribers convertidos (que viram usuários)
     */
    private function createConvertedSubscribers(int $count, array $sponsors): void
    {
        $conversionCampaigns = [
            'pre-launch-facebook' => ['count' => 45, 'avg_value' => 150],
            'pre-launch-google' => ['count' => 40, 'avg_value' => 180],
            'pre-launch-instagram' => ['count' => 30, 'avg_value' => 120],
            'early-bird-email' => ['count' => 20, 'avg_value' => 200],
            'vip-list-organic' => ['count' => 10, 'avg_value' => 250],
            'referral-program' => ['count' => 5, 'avg_value' => 300],
        ];

        foreach ($conversionCampaigns as $campaign => $config) {
            for ($i = 0; $i < $config['count']; $i++) {
                // 1. Criar o subscriber
                $subscriber = Subscriber::factory()->create([
                    'utm_campaign' => $campaign,
                    'status' => Subscriber::STATUS_CONVERTED,
                    'subscription_date' => fake()->dateTimeBetween('-45 days', '-10 days'),
                    'email_verified_at' => fake()->dateTimeBetween('-40 days', '-8 days'),
                    'converted_at' => fake()->dateTimeBetween('-8 days', 'now'),
                    'conversion_value' => fake()->randomFloat(2, $config['avg_value'] * 0.7, $config['avg_value'] * 1.3),
                    'sponsor_id' => fake()->optional(0.3)->randomElement($sponsors)?->id,
                ]);

                // 2. Criar o usuário correspondente
                $user = User::create([
                    'uuid' => fake()->uuid(),
                    'name' => $subscriber->name,
                    'email' => $subscriber->email,
                    'username' => fake()->unique()->userName(),
                    'phone' => $subscriber->phone,
                    'password' => Hash::make('password123'),
                    'email_verified_at' => $subscriber->email_verified_at,
                    'role' => 'customer',
                    'status' => 'active',
                ]);

                // 3. Vincular subscriber ao usuário
                $subscriber->update([
                    'converted_user_id' => $user->id,
                ]);
            }
        }
    }

    /**
     * Criar subscribers descadastrados
     */
    private function createUnsubscribedSubscribers(int $count): void
    {
        Subscriber::factory()
            ->count($count)
            ->state([
                'status' => Subscriber::STATUS_UNSUBSCRIBED,
                'subscription_date' => fake()->dateTimeBetween('-60 days', '-20 days'),
                'email_verified_at' => fake()->optional(0.5)->dateTimeBetween('-55 days', '-18 days'),
                'unsubscribed_at' => fake()->dateTimeBetween('-18 days', '-1 day'),
            ])
            ->create();
    }

    /**
     * Exibir estatísticas do seeding
     */
    private function displayStatistics(): void
    {
        $stats = [
            'total_subscribers' => Subscriber::count(),
            'pending' => Subscriber::where('status', 'pending')->count(),
            'active' => Subscriber::where('status', 'active')->count(),
            'converted' => Subscriber::where('status', 'converted')->count(),
            'unsubscribed' => Subscriber::where('status', 'unsubscribed')->count(),
            'total_users' => User::count(),
            'with_sponsor' => Subscriber::whereNotNull('sponsor_id')->count(),
        ];

        $conversionRate = $stats['total_subscribers'] > 0 
            ? round(($stats['converted'] / $stats['total_subscribers']) * 100, 2) 
            : 0;

        $totalConversionValue = (float) Subscriber::where('status', 'converted')
            ->sum('conversion_value');

        $this->command->info('');
        $this->command->info('📊 ESTATÍSTICAS DO SEEDING:');
        $this->command->info('================================');
        $this->command->info("📧 Total de Subscribers: {$stats['total_subscribers']}");
        $this->command->info("⏳ Pending: {$stats['pending']}");
        $this->command->info("✅ Active: {$stats['active']}");
        $this->command->info("🎯 Converted: {$stats['converted']}");
        $this->command->info("❌ Unsubscribed: {$stats['unsubscribed']}");
        $this->command->info("👥 Total de Users: {$stats['total_users']}");
        $this->command->info("🤝 Com Sponsor: {$stats['with_sponsor']}");
        $this->command->info("📈 Taxa de Conversão: {$conversionRate}%");
        $this->command->info("💰 Valor Total de Conversões: R$ " . number_format($totalConversionValue, 2, ',', '.'));
        $this->command->info('');

        // Log para auditoria
        Log::info('🌱 ' . __('app.subscriber.seeded'), [
            'total_subscribers' => $stats['total_subscribers'],
            'converted' => $stats['converted'],
            'conversion_rate' => $conversionRate,
            'total_value' => $totalConversionValue,
        ]);
    }
}
