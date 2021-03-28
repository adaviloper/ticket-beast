<?php

namespace Tests;

use App\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\TestResponse;
use PHPUnit\Framework\Assert;

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

        TestResponse::macro('data', function ($key) {
            return $this->original->getData()[$key];
        });

        EloquentCollection::macro('assertContains', function ($value) {
            Assert::assertTrue($this->contains($value), 'Failed asserting that the collection contained the specified value');
        });

        EloquentCollection::macro('assertNotContains', function ($value) {
            Assert::assertFalse($this->contains($value), 'Failed asserting that the collection did not contain the specified value');
        });

        EloquentCollection::macro('assertEquals', function ($items) {
            Assert::assertCount(count($this), $items);
            $this->zip($items)
                ->each(function ($pair) {
                    [$a, $b] = $pair;
                    Assert::assertTrue($a->is($b));
                });
        });
    }
}
