<?php

namespace App\Services;

use Aws\Lightsail\LightsailClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Cache;

class LightsailMetricsService
{
    protected $client;
    protected $instanceName;

    public function __construct()
    {
        $this->client = new LightsailClient([
            'version' => 'latest',
            'region' => config('services.aws.region'),
            'credentials' => [
                'key' => config('services.aws.key'),
                'secret' => config('services.aws.secret'),
            ],
        ]);

        $this->instanceName = config('services.lightsail.instance_name');
    }

    public function getMetrics()
    {
        // Cache for 1 minute to avoid excessive API calls
        return Cache::remember('lightsail_metrics', 60, function () {
            try {
                $endTime = time();
                $startTime = $endTime - 3600; // Last hour

                $metrics = [
                    'cpu' => $this->getMetricData('CPUUtilization', $startTime, $endTime),
                    'network_in' => $this->getMetricData('NetworkIn', $startTime, $endTime),
                    'network_out' => $this->getMetricData('NetworkOut', $startTime, $endTime),
                    'status_check_failed' => $this->getMetricData('StatusCheckFailed', $startTime, $endTime),
                ];

                return [
                    'success' => true,
                    'data' => $this->formatMetrics($metrics),
                ];
            } catch (AwsException $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    protected function getMetricData($metricName, $startTime, $endTime)
    {
        $result = $this->client->getInstanceMetricData([
            'instanceName' => $this->instanceName,
            'metricName' => $metricName,
            'period' => 300, // 5 minutes
            'startTime' => $startTime,
            'endTime' => $endTime,
            'unit' => $this->getUnit($metricName),
            'statistics' => ['Average', 'Maximum'],
        ]);

        return $result->get('metricData') ?? [];
    }

    protected function getUnit($metricName)
    {
        return match($metricName) {
            'CPUUtilization' => 'Percent',
            'NetworkIn', 'NetworkOut' => 'Bytes',
            'StatusCheckFailed' => 'Count',
            default => 'None',
        };
    }

    protected function formatMetrics($metrics)
    {
        $formatted = [];

        foreach ($metrics as $data => $dataPoints) {
            if (empty($dataPoints)) {
                $formatted[$data] = [
                    'current' => 0,
                    'average' => 0,
                    'maximum' => 0,
                ];
                continue;
            }

            $latest = end($dataPoints);
            $averages = array_column($dataPoints, 'average');
            $maximums = array_column($dataPoints, 'maximum');

            $formatted[$data] = [
                'current' => round($latest['average'] ?? 0, 2),
                'average' => round(array_sum($averages) / count($averages), 2),
                'maximum' => round(max($maximums), 2),
            ];
        }

        return $formatted;
    }
}
