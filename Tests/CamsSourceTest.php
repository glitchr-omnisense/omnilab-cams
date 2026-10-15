<?php

namespace Omnilab\Cams\Tests;

use Omnilab\Cams\CamsSourceFactory;
use Omnilab\Cds\CdsSource;
use Omnilab\Exception\NotSupportedException;
use Omnilab\Model\Point;
use Omnilab\Source\Capability;
use Omnilab\Source\ProductQuery;
use Omnilab\Source\Query;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Fixtures recorded from ads.atmosphere.copernicus.eu/api on 2026-10-15,
 * without an account: the Atmosphere Data Store's catalogue (its sixteen
 * datasets, each description cut to 300 characters), its search for "air
 * quality" (five a page), the European air quality forecasts and their
 * request form, the cost of a request for a day of PM2.5 over Strasbourg
 * (the forecast at 0 and 24 hours) and of one the store finds invalid
 * (four analysis times). Requests and jobs are omnilab/cds's, tested
 * there: the store's API is the same.
 */
final class CamsSourceTest extends TestCase
{
    /** @var list<array{string, string, list<string>}> */
    private array $calls = [];

    private function source(array $options = []): CdsSource
    {
        $http = new MockHttpClient(function (string $method, string $url, array $request): MockResponse {
            $this->calls[] = [$method, urldecode($url), $request['normalized_headers']['private-token'] ?? []];
            $path = (string) parse_url($url, \PHP_URL_PATH);
            $fixture = match (true) {
                str_ends_with($path, '/catalogue/v1/collections') => 'catalogue-collections.json',
                str_ends_with($path, '/catalogue/v1/datasets') => 'catalogue-datasets-air-quality.json',
                str_ends_with($path, '/collections/cams-europe-air-quality-forecasts') => 'collection-cams-europe-air-quality-forecasts.json',
                str_ends_with($path, '/processes/cams-europe-air-quality-forecasts') => 'process-cams-europe-air-quality-forecasts.json',
                str_ends_with($path, '/costing') => str_contains((string) $request['body'], '"analysis"') ? 'costing-invalid.json' : 'costing.json',
                default => null,
            };

            return null === $fixture
                ? new MockResponse('{"title":"Resource nope not found"}', ['http_code' => 404])
                : new MockResponse((string) file_get_contents(__DIR__.'/Fixtures/'.$fixture));
        });
        $source = (new CamsSourceFactory($http))->create($options);
        self::assertInstanceOf(CdsSource::class, $source);

        return $source;
    }

    public function testTheAtmosphereDataStoresDatasetsOverStrasbourg(): void
    {
        $page = $this->source()->products(new ProductQuery(place: new Point(48.5734, 7.7521), from: new \DateTimeImmutable('2026-10-14'), to: new \DateTimeImmutable('2026-10-14'), limit: 20));

        self::assertSame('https://ads.atmosphere.copernicus.eu/api/catalogue/v1/collections', $this->calls[0][1]);
        self::assertSame(6, $page->total, 'six of the sixteen run to that day');
        $ids = array_map(static fn ($p) => $p->id, $page->items);
        self::assertContains('cams-europe-air-quality-forecasts', $ids);
        self::assertNotContains('cams-global-reanalysis-eac4', $ids, 'a reanalysis that ends earlier');

        $europe = $this->source()->products(new ProductQuery(['cams-europe-air-quality-forecasts']))->first();
        self::assertSame(['CAMS European air quality forecasts', 'cams'], [$europe->properties['title'], $europe->source]);
        self::assertSame([-25.0, 30.0, 45.0, 72.0], $europe->bbox->toArray());
        self::assertSame('https://ads.atmosphere.copernicus.eu/datasets/cams-europe-air-quality-forecasts', $europe->url);
        self::assertSame(0, $this->source()->products(new ProductQuery(['cams-europe-air-quality-forecasts'], new Point(35.6895, 139.6917)))->total, 'not over Tokyo');
    }

    public function testTheSearchAndADatasetsVariables(): void
    {
        $source = $this->source();
        $page = $source->datasets(new Query('air quality', limit: 5));
        self::assertSame(['cams-europe-air-quality-forecasts', 'cams-europe-air-quality-reanalyses'], \array_slice(array_map(static fn ($p) => $p->id, $page->items), 0, 2));
        self::assertSame([7, '1'], [$page->total, $page->next]);

        $forecasts = $source->dataset('cams-europe-air-quality-forecasts');
        self::assertCount(28, $forecasts->properties['variables']);
        self::assertContains('particulate_matter_2.5um', $forecasts->properties['variables']);
        self::assertContains('ensemble', $forecasts->properties['inputs']['model']['values']);
        self::assertNull($source->dataset('nope'));
    }

    public function testWhatARequestWouldCost(): void
    {
        $source = $this->source();
        $request = ['variable' => ['particulate_matter_2.5um'], 'model' => ['ensemble'], 'level' => ['0'], 'date' => ['2026-10-10/2026-10-10'], 'type' => ['forecast'], 'time' => ['00:00'], 'leadtime_hour' => ['0', '24'], 'data_format' => 'netcdf_zip', 'area' => [49, 7, 48, 8]];

        self::assertSame(['cost' => 2.0, 'limit' => 5000.0, 'valid' => true, 'reason' => null], $source->cost('cams-europe-air-quality-forecasts', $request));
        self::assertSame(['POST', 'https://ads.atmosphere.copernicus.eu/api/retrieve/v1/processes/cams-europe-air-quality-forecasts/costing', []], $this->calls[0]);
        $invalid = $source->cost('cams-europe-air-quality-forecasts', ['type' => ['analysis']] + $request);
        self::assertFalse($invalid['valid']);
        self::assertStringStartsWith('Request has not produced a valid combination of values', (string) $invalid['reason']);
    }

    public function testNameOptionsAndKey(): void
    {
        self::assertSame('cams', (new CamsSourceFactory())->getName());
        self::assertSame('cams', $this->source()->getName());
        self::assertSame([Capability::PRODUCTS, Capability::AREA, Capability::DATASETS, Capability::JOBS, Capability::OPEN], $this->source()->capabilities());

        try {
            $this->source()->submit('cams-europe-air-quality-forecasts', []);
            self::fail('a submission without a key');
        } catch (NotSupportedException $e) {
            self::assertStringContainsString('"cams"', $e->getMessage());
        }
        self::assertSame([], $this->calls);
    }
}
