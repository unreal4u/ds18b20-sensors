<?php

declare(strict_types=1);

namespace unreal4u\DS18B20Sensor\Configuration;

abstract class BaseConfig {
    final public function __construct()
    {
        // Enable error reporting
        $this->setErrorReporting();
    }

    public function setErrorReporting(): self
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');

        return $this;
    }

    public function getMQTTCredentials(): array
    {
        return [
            'host' => 'localhost',
            'user' => '',
            'pass' => '',
            'clientId' => 'SensorWriter',
            'topicName' => 'sensors/temperature/',
        ];
    }

    public function getSensorDirectory(): string
    {
        return 'sensors/';
    }

    /**
     * Maybe for later: be able to transform the reported temperature reading to fahrenheit or kelvin
     *
     * @return string
     */
    public function getMeasurementUnit(): string
    {
        return 'celsius';
    }
}
