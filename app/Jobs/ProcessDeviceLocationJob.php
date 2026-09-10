<?php

namespace App\Jobs;

use App\Models\UserDevice;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique; // Added this
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Throwable;

class ProcessDeviceLocationJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 10;
    public int $backoff = 5;
    public int $uniqueFor = 600;
    
    protected string $deviceId;

    public function __construct(string $deviceId)
    {
        $this->deviceId = $deviceId;
    }

    public function uniqueId(): string
    {
        return 'device-location:' . $this->deviceId;
    }

    public function handle(): void
    {
        $device = UserDevice::find($this->deviceId);

        if (!$device) {
            Log::warning('GeoIP device not found', [
                'device_id' => $this->deviceId,
            ]);
            return;
        }

        $ipAddress = trim((string) $device->last_ip_address);

        if (empty($ipAddress)) {
            Log::warning('GeoIP skipped due to empty IP in DB', [
                'device_id' => $this->deviceId,
            ]);
            return;
        }

        if (
            app()->environment('local') &&
            (
                $ipAddress === '127.0.0.1' ||
                $ipAddress === '::1' ||
                str_starts_with($ipAddress, '192.168.')
            )
        ) {
            $ipAddress = '43.204.105.75';
        }

        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            Log::warning('GeoIP skipped due to invalid IP', [
                'device_id' => $this->deviceId,
                'ip' => $ipAddress,
            ]);
            return;
        }

        try {
            $response = Http::timeout(10)->get("http://ip-api.com/json/{$ipAddress}?fields=status,country,city,countryCode,timezone,lat,lon,proxy,hosting");

            if ($response->failed() || $response->json('status') !== 'success') {
                Log::warning('IP-API returned empty or failed result', [
                    'device_id' => $this->deviceId,
                    'ip' => $ipAddress,
                ]);
                return;
            }

            $data = $response->json();

            $country = $data['country'] ?? null;
            $city = $data['city'] ?? null;
            $countryCode = $data['countryCode'] ?? null;
            $timezone = $data['timezone'] ?? null;
            $lat = $data['lat'] ?? null;
            $lon = $data['lon'] ?? null;

            $isProxy = $data['proxy'] ?? false;
            $isHosting = $data['hosting'] ?? false;

            $vpnDetected = ($isProxy || $isHosting) ? 1 : 0;
            $proxyDetected = $isProxy ? 1 : 0;

            if (
                $device->last_country === $country &&
                $device->last_city === $city &&
                $device->country_code === $countryCode &&
                $device->vpn_detected === $vpnDetected
            ) {
                Log::info('GeoIP & Security Flags unchanged, skipping update', [
                    'device_id' => $this->deviceId,
                ]);
                $device->update(['location_updated_at' => now()]);

                return;
            }

            $device->update([
                'last_country' => $country,
                'last_city' => $city,
                'country_code' => $countryCode,
                'timezone' => $timezone,
                'lat' => $lat,
                'lon' => $lon,
                'vpn_detected' => $vpnDetected,
                'proxy_detected' => $proxyDetected,
                'location_updated_at' => now(),
            ]);

            Log::info('Device GeoIP & Security Flags updated successfully', [
                'device_id' => $this->deviceId,
                'ip' => $ipAddress,
                'country' => $country,
                'vpn' => $vpnDetected,
            ]);
        } catch (Throwable $e) {
            Log::error('GeoIP processing failed', [
                'device_id' => $this->deviceId,
                'ip' => $ipAddress ?? 'Unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('GeoIP job permanently failed', [
            'device_id' => $this->deviceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
