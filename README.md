# RPi3 DS18B20 PHP7 Sensor reader

This package will read out rpi3 1-wire sensors and send the output of those readings to a MQTT broker.

# Working of this package

**Step 0**:  
Ensure you've got a running php7 installation on your RPi3. How to do this falls outside the scope of this application.
Don't forget to install composer as well. I'll assume it's installed at `usr/bin/composer.phar`.

**Step 1**:  
Install the library in a fresh directory:

```bash
mkdir sensor-readout
cd sensor-readout
/bin/cat <<EOM >composer.json
{
  "minimum-stability": "dev",
  "require": {
    "unreal4u/ds18b20-sensor-read": "dev-master"
  }
}
EOM
composer.phar install -o --no-dev
```

**Step 2**:  
Create a folder `sensors/`, and in there create symlinks to the actual sensor id. Make the symlink a name for your
sensor, this name will be used for the topic name and general logging, for example: 

```bash
mkdir sensors
cd sensors/
ln -s /sys/bus/w1/devices/w1_bus_master1/XX-YYYYYYYYYYYY workshop
ln -s /sys/bus/w1/devices/w1_bus_master1/ZZ-ABCDEFABCDEF kitchen
```

Making the sensors actually work falls outside the scope of this application and you'll have to find out for yourself
how to do this.

**Step 3**:  
Create a configuration file. Create a new configuration file that extends `BaseConfig.php` and adjust the values that
you use in your environment:
```php
<?php
// Filename: app/ProductionConfig.php

declare(strict_types=1);

use unreal4u\DS18B20Sensor\Configuration\BaseConfig;

class ProductionConfig extends BaseConfig {
    public function getMQTTCredentials(): array
    {
        return array_merge(parent::getMQTTCredentials(), [
            'clientId' => 'sensorWriter', // Which clientId this client will pass on to the broker
            'host' => '192.168.1.45',     // The host of the broker
            'user' => 'XXXXXXXX',         // Optional username
            'pass' => 'YYYYYYYY',         // Optional password
        ]);
    }
}
```

**Step 4**:  
Create your proyect! I've included a small example of my current setup here, you can also take a look at the 
`examples/index.php` as an example and go ahead! I'll suppose you have created an `app` folder in which you have created
your own configuration and runnable file:
```php
<?php
// Filename: app/run.php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use unreal4u\DS18B20Sensor\Base;

chdir(__DIR__ . '/../');
include 'vendor/autoload.php';
include 'app/ProductionConfig.php';

// Initialize objects we'll need
$logger = new Logger('main');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
$baseProgram = new Base(new ProductionConfig(), $logger);
$baseProgram->runProgram();
$logger->info('Program finished running');
```

**Step 5**:  
Set up a cronjob that runs every X minutes in order to read out the sensors.

Cron examples:
```bash
# Every 2 minutes:
*/2 * * * * /usr/bin/php /home/pi/ds18b20-sensors-readout/app/run.php

# Every 1 minute:
* * * * * /usr/bin/php /home/pi/ds18b20-sensors-readout/app/run.php

# Every 5 minutes:
*/5 * * * * /usr/bin/php /home/pi/ds18b20-sensors-readout/app/run.php
```

# Known issues

- If there is any problem whatsoever with the sensor, this package will fail silently. This may be changed in the future
- No real usage of the Logger so far, this will also change in the future in order to establish more easily problematic areas
- Some idea for the future: be able to publish to multiple topics at the same time

Enjoy!
