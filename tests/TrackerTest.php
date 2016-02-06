<?php

require_once 'helpers/TrackerNonExistingConfig.php';
require_once 'helpers/TrackerEmptyConfig.php';
require_once 'helpers/TrackerNonExistingAtomFeed.php';
require_once 'helpers/TrackerGetAtomFeed.php';

class TrackerTest extends PHPUnit_Framework_TestCase
{
    public function testLoadConfigFile()
    {
    }

    public function testLoadNonExistingConfigFile()
    {
        new TrackerNonExistingConfig();
        $this->expectOutputString('Config.ini file is missing.' . PHP_EOL);
    }

    public function testLoadEmptyConfigFile()
    {
        new TrackerEmptyConfig();
        $this->expectOutputString('Empty config file.' . PHP_EOL);
    }

    /**
     * @expectedException \terenaa\TrackerScaffold\TrackerException
     * @expectedExceptionMessage Cannot load atom feed.
     */
    public function testGetNonExistingAtomFeed()
    {
        $this->callPrivateMethod('TrackerNonExistingAtomFeed', 'getAtomFeed');
    }

    public function testGetAtomFeed()
    {
        $feed = $this->callPrivateMethod('TrackerGetAtomFeed', 'getAtomFeed');
        $this->assertEquals('Test atom feed', $feed->channel->title, 'Wrong atom feed channel title');
    }

    private function callPrivateMethod($className, $methodName, array $args = array())
    {
        $class = new ReflectionClass($className);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs(new $className, $args);
    }
}
