<?php

namespace Tests;

use Laravel\Dusk\TestCase as BaseTestCase;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    protected const JOHN_EMAIL = 'john@example.com';
    protected const JANE_EMAIL = 'jane@example.com';

    public const GOOD_ORDER_CONFIRMATION_NUMBER = 'ORDERCONFIRMATION1234';
    public const BAD_ORDER_CONFIRMATION_NUMBER = 'ORDERCONFIRMATION1234';

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        static::startChromeDriver();
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        return RemoteWebDriver::create(
            'http://localhost:9515', DesiredCapabilities::chrome(), 5000, 10000
        );
    }
}
