<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\CaptureLeadRequest;
use App\Models\Subscriber;
use App\Services\Customer\SubscriberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class LeadCaptureController extends Controller
{
    public function __construct(
        private readonly SubscriberService $subscriberService
    ) {}

    /**
     * Capturar lead da landing page de pré-lançamento
     * 
     * Endpoint público para capturar emails da landing page
     * com tracking completo de UTM e device fingerprinting
     */
    public function capture(CaptureLeadRequest $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            // Rate limiting por IP (máximo 5 tentativas por minuto)
            $key = 'lead-capture:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('app.subscriber.rate_limit_exceeded'),
                    'errors' => ['rate_limit' => 'Too many attempts. Try again later.']
                ], 429);
            }

            RateLimiter::hit($key, 60); // 1 minuto

            // Verificar se email já existe
            $existingSubscriber = Subscriber::byEmail($request->email)->first();
            if ($existingSubscriber) {
                return response()->json([
                    'status' => 'success',
                    'message' => __('app.subscriber.already_subscribed'),
                    'data' => [
                        'subscriber_uuid' => $existingSubscriber->uuid,
                        'status' => $existingSubscriber->status,
                        'already_exists' => true,
                    ],
                    'meta' => ['execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)]
                ], 200);
            }

            // Capturar lead com tracking completo
            $result = $this->subscriberService->createFromLandingPage(
                $request->validated(),
                $request
            );

            return response()->json([
                'status' => 'success',
                'message' => __('app.subscriber.created'),
                'data' => [
                    'subscriber_uuid' => $result['subscriber']->uuid,
                    'email' => $result['subscriber']->email,
                    'status' => $result['subscriber']->status,
                    'tracking_source' => $result['tracking_info']['utm_source'],
                    'tracking_campaign' => $result['tracking_info']['utm_campaign'],
                    'next_steps' => [
                        'verification_email' => 'Check your email for verification link',
                        'early_access' => 'You will be notified about early access',
                    ],
                ],
                'meta' => ['execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)]
            ], 201);

        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Race condition - email foi inserido entre a verificação e o insert
            return response()->json([
                'status' => 'success',
                'message' => __('app.subscriber.already_subscribed'),
                'data' => ['already_exists' => true],
                'meta' => ['execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ ' . __('app.subscriber.capture_failed'), [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => __('app.subscriber.capture_failed'),
                'errors' => ['system' => 'Unable to process your request. Please try again.']
            ], 500);
        }
    }

    /**
     * Verificar status de um lead
     * 
     * Endpoint para verificar se um lead existe pelo UUID
     * e qual o status atual (útil para UX da landing page)
     */
    public function checkStatus(string $uuid): JsonResponse
    {
        $subscriber = Subscriber::where('uuid', $uuid)->first();

        if (!$subscriber) {
            return response()->json([
                'status' => 'error',
                'message' => __('app.subscriber.not_found'),
                'errors' => ['uuid' => 'Subscriber not found'],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('app.subscriber.status_found'),
            'data' => [
                'uuid' => $subscriber->uuid,
                'status' => $subscriber->status,
                'subscribed_at' => $subscriber->subscription_date->toISOString(),
                'preferences' => $subscriber->preferences,
            ],
        ], 200);
    }

    /**
     * Descadastrar lead (unsubscribe)
     * 
     * Endpoint público para descadastro via UUID (link em email)
     */
    public function unsubscribe(string $uuid): JsonResponse
    {
        $subscriber = Subscriber::where('uuid', $uuid)->first();

        if (!$subscriber) {
            return response()->json([
                'status' => 'error',
                'message' => __('app.subscriber.not_found'),
                'errors' => ['uuid' => 'Subscriber not found']
            ], 404);
        }

        if ($subscriber->status === Subscriber::STATUS_UNSUBSCRIBED) {
            return response()->json([
                'status' => 'success',
                'message' => __('app.subscriber.already_unsubscribed'),
                'data' => ['unsubscribed_at' => $subscriber->unsubscribed_at?->toISOString()],
            ], 200);
        }

        $subscriber->unsubscribe();

        Log::info('📧 ' . __('app.subscriber.unsubscribed'), [
            'subscriber_uuid' => $subscriber->uuid,
            'email' => $subscriber->email,
            'previous_status' => $subscriber->getOriginal('status'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('app.subscriber.unsubscribed'),
            'data' => [
                'unsubscribed_at' => $subscriber->unsubscribed_at?->toISOString(),
                'status' => $subscriber->status,
            ],
        ], 200);
    }
}
