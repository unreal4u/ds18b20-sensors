<?php

declare(strict_types=1);

namespace unreal4u\DS18B20Sensor\Configuration;

class DevelopmentConfig extends BaseConfig {
    public function getMQTTCredentials(): array
    {
        return array_merge([
            'clientId' => 'developmentWriter',
        ], parent::getMQTTCredentials());
    }

    public function getSensorDirectory(): string
    {
        return 'tests/sensors/';
    }
}
