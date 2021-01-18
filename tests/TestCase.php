<?php

namespace Tests;

use App\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected const JOHN_EMAIL = 'john@example.com';
    protected const JANE_EMAIL = 'jane@example.com';

    public const GOOD_ORDER_CONFIRMATION_NUMBER = 'ORDERCONFIRMATION1234';
    public const BAD_ORDER_CONFIRMATION_NUMBER = 'ORDERCONFIRMATION1234';

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    protected function setUp()
    {
        parent::setUp();
        Mockery::getConfiguration()->allowMockingNonExistentMethods(false);
    }

    public function disableExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, new class extends Handler {
            public function __construct() {}
            public function report(\Exception $exception) {}
            public function render($request, \Exception $exception) {
                throw $exception;
            }
        });
    }
}
