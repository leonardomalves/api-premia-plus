<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminDirectSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Criando administradores diretamente no banco...');

        $admins = [
            [
                'name' => 'Administrador Principal',
                'email' => 'admin@premiaplus.com',
                'username' => 'admin',
                'password' => 'password',
                'phone' => '11999999999',
                'role' => 'admin',
            ],
            [
                'name' => 'Super Administrador',
                'email' => 'superadmin@premiaplus.com',
                'username' => 'superadmin',
                'password' => 'password',
                'phone' => '11998888888',
                'role' => 'admin',
            ],
            [
                'name' => 'Administrador Financeiro',
                'email' => 'admin.financeiro@premiaplus.com',
                'username' => 'admin_financeiro',
                'password' => 'password',
                'phone' => '11997777777',
                'role' => 'admin',
            ],
            [
                'name' => 'Administrador de Sistema',
                'email' => 'admin.sistema@premiaplus.com',
                'username' => 'admin_sistema',
                'password' => 'password',
                'phone' => '11996666666',
                'role' => 'admin',
            ],
            [
                'name' => 'Administrador de Suporte',
                'email' => 'admin.suporte@premiaplus.com',
                'username' => 'admin_suporte',
                'password' => 'password',
                'phone' => '11995555555',
                'role' => 'admin',
            ],
        ];

        $successCount = 0;
        $errorCount = 0;

        foreach ($admins as $adminData) {
            try {
                // Verificar se o admin já existe
                $existingUser = User::where('email', $adminData['email'])
                    ->orWhere('username', $adminData['username'])
                    ->first();

                if ($existingUser) {
                    $this->command->warn("⚠️ {$adminData['name']} já existe no banco");

                    continue;
                }

                // Criar o usuário admin diretamente no banco
                $admin = User::create([
                    'uuid' => Str::uuid(),
                    'name' => $adminData['name'],
                    'email' => $adminData['email'],
                    'username' => $adminData['username'],
                    'password' => Hash::make($adminData['password']),
                    'phone' => $adminData['phone'],
                    'role' => $adminData['role'],
                    'email_verified_at' => now(),
                    'sponsor_id' => null, // Admin não tem sponsor
                    'status' => 'active',
                ]);

                $this->command->info("✅ {$adminData['name']} criado com sucesso!");
                $this->command->line("   📧 Email: {$admin->email}");
                $this->command->line("   👤 Username: {$admin->username}");
                $this->command->line("   🆔 UUID: {$admin->uuid}");

                $successCount++;

            } catch (\Exception $e) {
                $this->command->error("❌ Erro ao criar {$adminData['name']}: {$e->getMessage()}");
                $errorCount++;
            }
        }

        // Resumo
        $total = count($admins);
        $this->command->info('📊 RESUMO DA CRIAÇÃO DE ADMINS');
        $this->command->line('═══════════════════════════════');
        $this->command->info("✅ Admins criados: {$successCount}");
        if ($errorCount > 0) {
            $this->command->error("❌ Falhas: {$errorCount}");
        }
        $this->command->line("📊 Total processados: {$total}");
        $this->command->line('═══════════════════════════════');

        // Verificar criação
        $this->verifyAdminsCreated();

        $this->command->info('✅ Administradores criados diretamente no banco!');
    }

    /**
     * Verificar se os admins foram criados corretamente
     */
    private function verifyAdminsCreated(): void
    {
        $this->command->info('🔍 Verificando administradores criados...');

        $admins = User::where('role', 'admin')->get();

        if ($admins->count() > 0) {
            $this->command->info("👥 {$admins->count()} administrador(es) encontrado(s) no banco:");

            foreach ($admins as $admin) {
                $this->command->line("   • {$admin->name} ({$admin->email})");
            }

            $this->command->info('✅ Verificação concluída!');
        } else {
            $this->command->error('❌ Nenhum administrador encontrado no banco!');
        }
    }
}
