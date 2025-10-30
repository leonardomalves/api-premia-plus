<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Dados básicos do lead
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('country')->default('BR');
            
            // Rastreamento de origem da campanha (UTM Parameters)
            $table->string('utm_source')->nullable(); // google, facebook, instagram, etc
            $table->string('utm_medium')->nullable(); // cpc, organic, social, email, etc
            $table->string('utm_campaign')->nullable(); // nome da campanha
            $table->string('utm_term')->nullable(); // palavras-chave
            $table->string('utm_content')->nullable(); // variação do anúncio
            $table->string('referrer_url')->nullable(); // URL de origem
            $table->json('tracking_data')->nullable(); // dados adicionais de tracking
            
            // Status e dados do lead
            $table->enum('status', ['pending', 'active', 'converted', 'unsubscribed'])->default('pending');
            $table->timestamp('subscription_date')->useCurrent();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            
            // Dados de conversão para cliente
            $table->foreignId('converted_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('sponsor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('converted_at')->nullable();
            $table->decimal('conversion_value', 10, 2)->nullable(); // valor da primeira compra/plano
            
            // Dados técnicos de rastreamento
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('device_info')->nullable(); // mobile, desktop, browser, OS
            $table->json('preferences')->nullable(); // preferências de comunicação
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para performance e relatórios
            $table->index(['status', 'created_at']);
            $table->index(['utm_source', 'utm_campaign']);
            $table->index(['converted_at']);
            $table->index(['sponsor_id']);
            $table->index(['subscription_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
