<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessPosterImage;
use ConcertFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessPosterImageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_resizes_the_poster_image_to_600px_wide(): void
    {
        Storage::fake();
        Storage::put(
            'posters/example-poster.png',
            file_get_contents(base_path('tests/__fixtures__/full-size-poster.png'))
        );
        $concert = ConcertFactory::createUnpublished([
            'poster_image_path' => 'posters/example-poster.png',
        ]);

        ProcessPosterImage::dispatch($concert);

        $resizedImage = Storage::get('posters/example-poster.png');
        list($width, $height) = getimagesizefromstring($resizedImage);

        self::assertEquals(600, $width);
        self::assertEquals(776, $height);

        $resizedImageContents = Storage::get('posters/example-poster.png');
        $controlImageContents = file_get_contents(base_path('tests/__fixtures__/optimized-poster.png'));
        self::assertEquals($controlImageContents, $resizedImageContents);
    }

    /** @test */
    public function it_optimizes_the_poster_image(): void
    {
        Storage::fake();
        Storage::put(
            'posters/example-poster.png',
            file_get_contents(base_path('tests/__fixtures__/small-unoptimized-poster.png'))
        );
        $concert = ConcertFactory::createUnpublished([
            'poster_image_path' => 'posters/example-poster.png',
        ]);

        ProcessPosterImage::dispatch($concert);

        $optimizedImageSize = Storage::size('posters/example-poster.png');
        $originalSize = filesize(base_path('tests/__fixtures__/small-unoptimized-poster.png'));
        self::assertLessThan($originalSize, $optimizedImageSize);

        $optimizedImageContents = Storage::get('posters/example-poster.png');
        $controlImageContents = file_get_contents(base_path('tests/__fixtures__/optimized-poster.png'));
        self::assertEquals($controlImageContents, $optimizedImageContents);
    }
}
