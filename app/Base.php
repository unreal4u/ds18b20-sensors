<?php

declare(strict_types=1);

namespace unreal4u\DS18B20Sensor;

use Psr\Log\LoggerInterface;
use unreal4u\DS18B20Sensor\Configuration\BaseConfig;
use unreal4u\FileOperations\FileContentsGetter;
use unreal4u\MQTT\Client;
use unreal4u\MQTT\DataTypes\Message;
use unreal4u\MQTT\DataTypes\TopicName;
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
        $this->logger->debug('Assigning basic variables');

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
        $connectionParameters->setCredentials($mqttConnectionParameters['user'], $mqttConnectionParameters['pass']);

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
            $this->MQTTClient->processObject($connect);
            if ($this->MQTTClient->isConnected()) {
                $this->logger->debug('Connected to broker successfully');
            } else {
                throw new \RuntimeException('Could not connect to broker');
            }
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
        $publishMessage = new Publish();

        $this->fileContentsGetter->constructFileList(
            $mainDirectory,
            ['recursive' => true, 'pattern' => '#/\w+/w1_slave$#', 'maxDepth' => 1]
        )->perform();

        $sensorCount = 0;
        foreach ($this->fileContentsGetter->getOutput() as $filename => $fileContents) {
            $this->logger->debug('Extracting temperature and sensorName', ['filename' => $filename]);
            $temperature = $this->extractTemperature($fileContents);
            // This strange construct eases development: it counts the number of directories and returns the relevant
            $sensorName = explode('/', $filename)[substr_count($mainDirectory, '/')];
            $this->logger->info('Temperature reading done', [
                'filename' => $filename,
                'sensorName' => $sensorName,
                'temperature' => $temperature,
            ]);


            $message = new Message(
                $temperature,
                new TopicName($this->config->getMQTTCredentials()['topicName'] . $sensorName)
            );
            $message->setRetainFlag(true);

            $publishMessage->setMessage($message);
            try {
                $this->MQTTClient->processObject($publishMessage);
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                die();
            }
            $sensorCount++;
        }
        $this->logger->debug('Finished run', ['numberOfSensors' => $sensorCount]);

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
