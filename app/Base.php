<?php

declare(strict_types=1);

namespace unreal4u\DS18B20Sensor;

use Psr\Log\LoggerInterface;
use unreal4u\DS18B20Sensor\Configuration\BaseConfig;
use unreal4u\FileOperations\FileContentsGetter;
use unreal4u\MQTT\Application\Message;
use unreal4u\MQTT\Client;
use unreal4u\MQTT\Internals\ClientInterface;
use unreal4u\MQTT\Protocol\Connect;
use unreal4u\MQTT\Protocol\Connect\Parameters;
use unreal4u\MQTT\Protocol\Publish;

final class Base {
    /**
     * @var BaseConfig
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FileContentsGetter
     */
    private $fileContentsGetter;

    /**
     * @var ClientInterface
     */
    private $MQTTClient;

    /**
     * Will set some common objects needed for the execution of the program
     *
     * @param BaseConfig $config
     * @param LoggerInterface $logger
     */
    public function __construct(BaseConfig $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;

        $this->initializeVariables();
    }

    /**
     * Initializes more variables
     * @return Base
     */
    private function initializeVariables(): self
    {
        $this->fileContentsGetter = new FileContentsGetter();
        $mqttConnectionParameters = $this->config->getMQTTCredentials();

        $connectionParameters = new Parameters($mqttConnectionParameters['clientId'], $mqttConnectionParameters['host']);
        $connectionParameters->setUsername($mqttConnectionParameters['user']);
        $connectionParameters->setPassword($mqttConnectionParameters['pass']);

        $connect = new Connect();
        $connect->setConnectionParameters($connectionParameters);

        $this->MQTTClient = new Client();
        $this->connectToMQTTBroker($connect);

        return $this;
    }

    /**
     * With the parameters ready, will try to connect to a MQTT broker
     *
     * @param Connect $connect
     * @return Base
     */
    private function connectToMQTTBroker(Connect $connect): self
    {
        try {
            $this->MQTTClient->sendData($connect);
            $this->logger->debug('Connected to broker successfully');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            die();
        }

        return $this;
    }

    /**
     * Reads the actual sensors out and sends the output to a MQTT broker
     *
     * @return Base
     */
    public function runProgram(): self
    {
        $mainDirectory = $this->config->getSensorDirectory();
        $message = new Message();
        $publishMessage = new Publish();

        $this->fileContentsGetter->constructFileList(
            $mainDirectory,
            ['recursive' => true, 'pattern' => '#w1_slave#']
        )->perform();

        $fileList = $this->fileContentsGetter->getOutput();
        $this->logger->debug('Retrieved list of sensors', array_keys($fileList));

        foreach ($fileList as $filename => $fileContents) {
            $temperature = $this->extractTemperature($fileContents);
            $sensorName = explode('/', $filename)[substr_count($mainDirectory, '/')];
            $this->logger->info('Temperature reading done', [
                'sensorName' => $sensorName,
                'temperature' => $temperature
            ]);

            $message->setTopicName($this->config->getMQTTCredentials()['topicName'] . $sensorName);
            $message->setPayload($temperature);

            $publishMessage->setMessage($message);

            try {
                $this->MQTTClient->sendData($publishMessage);
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                die();
            }
        }
        $this->logger->debug('Finished run', ['numberOfSensors' => \count($fileList)]);

        return $this;
    }

    /**
     * Transforms raw sensor data to a human readable format
     *
     * @param string $fileContents
     * @return string
     */
    private function extractTemperature(string $fileContents): string
    {
        $dataLines = explode(PHP_EOL, $fileContents);
        $rawTemperatureData = substr($dataLines[1], strpos($dataLines[1], ' t=') + 3);
        // Strange construct: count the number of slashes in $mainDirectory and this will be the index of the sensor's name
        $rawTemperature = $rawTemperatureData / 1000;
        $this->logger->debug('Raw temperature retrieved', ['rawTemperature' => $rawTemperatureData]);
        // TODO Transform to Kelvin or Fahrenheit
        return sprintf('%.1f', $rawTemperature);
    }
}
