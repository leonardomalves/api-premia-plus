<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Services\BusinessRules\UpLinesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpLinesServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_finds_uplines_correctly()
    {
        // Criar hierarquia: A -> B -> C
        $userA = User::factory()->create(['username' => 'userA']);
        $userB = User::factory()->create(['username' => 'userB', 'sponsor_id' => $userA->id]);
        $userC = User::factory()->create(['username' => 'userC', 'sponsor_id' => $userB->id]);

        $order = Order::factory()->create(['user_id' => $userC->id]);

        $upLinesService = new UpLinesService();
        $result = $upLinesService->run($order);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['uplines']);
        
        // Verificar ordem dos uplines
        $this->assertEquals($userB->id, $result['uplines'][0]['id']);
        $this->assertEquals(1, $result['uplines'][0]['level']);
        
        $this->assertEquals($userA->id, $result['uplines'][1]['id']);
        $this->assertEquals(2, $result['uplines'][1]['level']);
    }

    public function test_handles_user_without_sponsor()
    {
        $user = User::factory()->create(['sponsor_id' => null]);
        $order = Order::factory()->create(['user_id' => $user->id]);

        $upLinesService = new UpLinesService();
        $result = $upLinesService->run($order);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['uplines']);
        $this->assertEquals('Sem patrocinador', $result['message']);
    }

    public function test_respects_max_levels_configuration()
    {
        // Criar hierarquia com 5 níveis
        $users = [];
        $users[0] = User::factory()->create(['username' => 'level0']);
        
        for ($i = 1; $i < 5; $i++) {
            $users[$i] = User::factory()->create([
                'username' => "level{$i}",
                'sponsor_id' => $users[$i-1]->id
            ]);
        }

        $order = Order::factory()->create(['user_id' => $users[4]->id]);

        $upLinesService = new UpLinesService();
        $upLinesService->setMaxLevels(2); // Limitar a 2 níveis

        $result = $upLinesService->run($order);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['uplines']); // Apenas 2 níveis
    }
}