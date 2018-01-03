# RPi3 DS18B20 PHP7 Sensor reader

This package will read out rpi3 1-wire sensors and send the output of those readings to a MQTT broker.

# Working of this package

**Step 0**:  
Ensure you've got a running php7 installation on your RPi3. How to do this falls outside the scope of this application.
Don't forget to install composer as well. I'll assume it's installed at `usr/bin/composer.phar`.

**Step 1**:  
Clone this repo and install dependencies:

```bash
cd /home/pi/
git clone https://github.com/unreal4u/ds18b20-sensors.git
cd ds18b20-sensors/
composer.phar install -o
```

**Step 2**:  
In the folder `sensors/`, create symlinks to the actual sensor id. Make the symlink a name for your sensor, this name
will be used for the topic name and general logging, for example: 

```bash
cd sensors/
ln -s /sys/bus/w1/devices/w1_bus_master1/28-02162cdff1ee workshop
```

Making the sensors actually work falls outside the scope of this application and you'll have to find out for yourself
how to do this.

**Step 3**:  
In the folder `app/Configuration/`, create a new configuration file that extends `BaseConfig.php` and adjust the values
that you use in your environment. Take `DevelopmentConfig.php` as an example.

**Step 4**:  
Create your proyect! Take `examples/index.php` as an example and go ahead! I'll suppose you have created an `app` folder
in which you have created your own configuration and runnable file.

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

Enjoy!
