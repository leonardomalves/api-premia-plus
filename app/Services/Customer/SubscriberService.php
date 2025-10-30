<?php

declare(strict_types=1);

namespace App\Services\Customer;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SubscriberService
{
    /**
     * Criar novo subscriber a partir dos dados da landing page
     */
    public function createFromLandingPage(array $data, Request $request): array
    {
        $trackingData = $this->extractTrackingData($request);
        
        $subscriber = Subscriber::create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'country' => $data['country'] ?? 'BR',
            'utm_source' => $trackingData['utm_source'],
            'utm_medium' => $trackingData['utm_medium'],
            'utm_campaign' => $trackingData['utm_campaign'],
            'utm_term' => $trackingData['utm_term'],
            'utm_content' => $trackingData['utm_content'],
            'referrer_url' => $trackingData['referrer_url'],
            'tracking_data' => $trackingData['additional_data'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_info' => $this->extractDeviceInfo($request),
            'preferences' => $data['preferences'] ?? [
                'newsletter' => true,
                'promotions' => true,
                'updates' => true,
            ],
        ]);

        Log::info('📧 ' . __('app.subscriber.created'), [
            'subscriber_uuid' => $subscriber->uuid,
            'email' => $subscriber->email,
            'utm_source' => $subscriber->utm_source,
            'utm_campaign' => $subscriber->utm_campaign,
        ]);

        return [
            'subscriber' => $subscriber,
            'tracking_info' => $trackingData,
        ];
    }

    /**
     * Converter subscriber em user (cliente)
     */
    public function convertToUser(Subscriber $subscriber, User $user, ?User $sponsor = null, ?float $conversionValue = null): void
    {
        $subscriber->convertToUser($user, $sponsor, $conversionValue);

        Log::info('🎯 ' . __('app.subscriber.converted'), [
            'subscriber_uuid' => $subscriber->uuid,
            'user_uuid' => $user->uuid,
            'sponsor_uuid' => $sponsor?->uuid,
            'conversion_value' => $conversionValue,
            'utm_source' => $subscriber->utm_source,
            'utm_campaign' => $subscriber->utm_campaign,
        ]);
    }

    /**
     * Relatório de conversões por período
     */
    public function getConversionReport(string $startDate, string $endDate): array
    {
        $cacheKey = "conversion_report_{$startDate}_{$endDate}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($startDate, $endDate) {
            $subscribers = Subscriber::query()
                ->whereBetween('subscription_date', [$startDate, $endDate])
                ->get();

            $converted = $subscribers->where('status', Subscriber::STATUS_CONVERTED);

            $bySource = $subscribers->groupBy('utm_source')->map(function ($group) {
                $total = $group->count();
                $converted = $group->where('status', Subscriber::STATUS_CONVERTED)->count();
                
                return [
                    'total' => $total,
                    'converted' => $converted,
                    'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 2) : 0,
                    'total_value' => $group->where('status', Subscriber::STATUS_CONVERTED)->sum('conversion_value'),
                ];
            });

            $byCampaign = $subscribers->groupBy('utm_campaign')->map(function ($group) {
                $total = $group->count();
                $converted = $group->where('status', Subscriber::STATUS_CONVERTED)->count();
                
                return [
                    'total' => $total,
                    'converted' => $converted,
                    'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 2) : 0,
                    'total_value' => $group->where('status', Subscriber::STATUS_CONVERTED)->sum('conversion_value'),
                ];
            });

            return [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'summary' => [
                    'total_subscribers' => $subscribers->count(),
                    'total_converted' => $converted->count(),
                    'conversion_rate' => $subscribers->count() > 0 ? round(($converted->count() / $subscribers->count()) * 100, 2) : 0,
                    'total_conversion_value' => $converted->sum('conversion_value'),
                    'average_conversion_value' => $converted->count() > 0 ? round($converted->avg('conversion_value'), 2) : 0,
                ],
                'by_source' => $bySource,
                'by_campaign' => $byCampaign,
            ];
        });
    }

    /**
     * Extrair dados de tracking da requisição
     */
    private function extractTrackingData(Request $request): array
    {
        return [
            'utm_source' => $request->get('utm_source'),
            'utm_medium' => $request->get('utm_medium'),
            'utm_campaign' => $request->get('utm_campaign'),
            'utm_term' => $request->get('utm_term'),
            'utm_content' => $request->get('utm_content'),
            'referrer_url' => $request->header('referer'),
            'additional_data' => [
                'landing_page' => $request->fullUrl(),
                'timestamp' => now()->toISOString(),
                'session_id' => $request->session()->getId(),
            ],
        ];
    }

    /**
     * Extrair informações do dispositivo
     */
    private function extractDeviceInfo(Request $request): array
    {
        $userAgent = $request->userAgent() ?? '';
        
        return [
            'device' => $this->detectDevice($userAgent),
            'browser' => $this->detectBrowser($userAgent),
            'os' => $this->detectOS($userAgent),
            'is_mobile' => $request->header('X-Requested-With') === 'XMLHttpRequest' || 
                          str_contains(strtolower($userAgent), 'mobile'),
        ];
    }

    private function detectDevice(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);
        
        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'iphone')) {
            return 'mobile';
        } elseif (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }
        
        return 'desktop';
    }

    private function detectBrowser(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);
        
        if (str_contains($userAgent, 'chrome')) return 'Chrome';
        if (str_contains($userAgent, 'firefox')) return 'Firefox';
        if (str_contains($userAgent, 'safari')) return 'Safari';
        if (str_contains($userAgent, 'edge')) return 'Edge';
        
        return 'Unknown';
    }

    private function detectOS(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);
        
        if (str_contains($userAgent, 'windows')) return 'Windows';
        if (str_contains($userAgent, 'mac')) return 'macOS';
        if (str_contains($userAgent, 'linux')) return 'Linux';
        if (str_contains($userAgent, 'android')) return 'Android';
        if (str_contains($userAgent, 'ios') || str_contains($userAgent, 'iphone')) return 'iOS';
        
        return 'Unknown';
    }
}