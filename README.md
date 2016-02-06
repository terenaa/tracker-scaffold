# PHP Tracker scaffold
Simple scaffold for PHP trackers

Requirements:
* PHP >= 5.3.2
* [terenaa\SmsGateway](https://github.com/terenaa/sms-gateway)

## Installation
Clone tracker scaffold code from repository
```bash
git clone https://github.com/terenaa/tracker-scaffold.git
```
Add your new class(es) namespace(s) to autoload in composer.json
```
"autoload": {
  "psr-4": {
    "terenaa\\TrackerScaffold\\": "src/terenaa/TrackerScaffold/",
    "myNamespace\\": "src/myName/"
  }
}
```
Download Composer dependencies and generate autoload file
```bash
composer update
```
Copy and fill config.ini.example file (at least `atom_url`)
```bash
cp config.ini.example config.ini
```
Implement `getLastEntry()` method and run your tracker
## Example

### TestTracker.php
```php
namespace terenaa\trackers;

class TestTracker extends \terenaa\TrackerScaffold\Tracker
{
    protected function getLastEntry()
    {
        $feed = $this->getAtomFeed();

        if (strpos($feed->channel->item[0]->title, 'Some value') !== false) {
            return array(
                'guid' => $feed->channel->item[0]->guid,
                'title' => $feed->channel->item[0]->title,
                'desc' => $feed->channel->item[0]->description
            );
        }

        return null;
    }
}
```

### runner.php
```php
require_once 'vendor/autoload.php';

$tracker = new \terenaa\trackers\TestTracker();
$tracker->run(true);
```