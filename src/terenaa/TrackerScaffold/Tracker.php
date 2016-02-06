<?php
/**
 * Simple scaffold for PHP trackers
 *
 * PHP Version 5
 *
 * @category Scaffold
 * @author Krzysztof Janda <terenaa@the-world.pl>
 * @license https://opensource.org/licenses/MIT MIT
 * @version 1.0
 * @link https://www.github.com/terenaa/tracker-scaffold
 */

namespace terenaa\TrackerScaffold;

use terenaa\SmsGateway\SmsGateway;
use terenaa\SmsGateway\SmsGatewayException;


/**
 * Class Tracker
 * @package terenaa\TrackerScaffold
 */
abstract class Tracker
{
    const configPath = '/../../../config/config.ini';
    const cachePath = '/../../../config/tracker.cache';

    private $config;

    /**
     * Tracker constructor.
     */
    public function __construct()
    {
        try {
            $this->loadConfig();
        } catch (TrackerException $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * Run the tracker
     *
     * @param bool $infinite run in infinite loop
     */
    public function run($infinite = false)
    {
        do {
            try {
                $entry = $this->getLastEntry();

                echo PHP_EOL . date('Y-m-d H:i:s ');

                if ($entry && $this->getCache() != $entry['guid']) {
                    $this->setCache($entry['guid']);
                    echo $entry['title'];

                    if ($this->config['linux_notify']) {
                        exec("notify-send --urgency=critical --expire-time=5000 --icon={$this->config['linux_icon']} '{$entry['title']}'");
                    }

                    if ($this->config['phone_notify']) {
                        try {
                            $sms = new SmsGateway();
                            $sms->send($this->config['phone_number'], substr($entry['title'], 0, 77) . '...');
                        } catch (SmsGatewayException $e) {
                            echo $this->info($e->getMessage());
                        }
                    }

                    if ($this->config['email_notify']) {
                        try {
                            $this->sendMail($this->config['email_address'], $this->config['email_from_name'], $this->config['email_from'], $entry['title'], $entry['desc']);
                        } catch (TrackerException $e) {
                            echo $this->info($e->getMessage());
                        }
                    }
                }
            } catch (TrackerException $e) {
                echo $this->info($e->getMessage());
            }

            if ($infinite) {
                sleep($this->config['refresh']);
            }
        } while ($infinite);

        echo PHP_EOL;
    }

    /**
     * Get atom feed from file or www
     *
     * @return \SimpleXMLElement
     * @throws TrackerException
     */
    protected function getAtomFeed()
    {
        if (!($feed = simplexml_load_file($this->config['atom_url'], 'SimpleXMLElement', LIBXML_NOWARNING | LIBXML_NOERROR))) {
            throw new TrackerException('Cannot load atom feed.');
        }

        return $feed;
    }

    /**
     * Get last atom feed entry and verify if this is in the circle of interest
     *
     * This method is expected to return array which contains at least 'guid' and 'title' keys on success or null on failure;
     * 'desc' key is optional and only used when e-mail notification is on
     *
     * @return mixed
     */
    abstract protected function getLastEntry();

    /**
     * Load configuration from file
     *
     * @throws TrackerException
     */
    private function loadConfig()
    {
        if (!file_exists(__DIR__ . static::configPath)) {
            throw new TrackerException('Config.ini file is missing.');
        }

        if (!($this->config = parse_ini_file(__DIR__ . static::configPath))) {
            throw new TrackerException('Empty config file.');
        }
    }

    /**
     * Get cached information about last atom feed entry
     *
     * @return null|string
     */
    private function getCache()
    {
        if (file_exists(__DIR__ . static::cachePath)) {
            return file_get_contents(__DIR__ . static::cachePath);
        }

        return null;
    }

    /**
     * Save information about last atom feed entry
     *
     * @param string $guid id of last atom feed entry
     * @return int number of bytes that were written to the file, or false on failure
     */
    private function setCache($guid)
    {
        return file_put_contents(__DIR__ . static::cachePath, $guid);
    }

    /**
     * Send mail with last atom feed entry
     *
     * @param string $to message recipient
     * @param string $fromUser name of message sender
     * @param string $fromMail e-mail address of message sender
     * @param string $subject subject of the message
     * @param string $message the message
     * @return bool true if the mail was successfully accepted for delivery, false otherwise
     * @throws TrackerException
     */
    private function sendMail($to, $fromUser, $fromMail, $subject = '(No subject)', $message = '')
    {
        $fromUser = "=?UTF-8?B?" . base64_encode($fromUser) . "?=";
        $subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        $headers = "From: {$fromUser} <$fromMail>\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "X-Mailer: PHP/" . phpversion();

        if (!mail($to, $subject, $message, $headers)) {
            throw new TrackerException('Cannot send the message via e-mail.');
        }

        return true;
    }

    /**
     * Prepare message to display on the screen
     *
     * @param string $message message to prepare
     * @return string prepared info message
     */
    private function info($message)
    {
        return " | $message";
    }
}
