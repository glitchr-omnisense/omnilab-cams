# omnilab/cams

The Copernicus Atmosphere Monitoring Service's data through its Atmosphere Data Store
(`https://ads.atmosphere.copernicus.eu/api/`: the catalogue `catalogue/v1`, STAC; the requests
`retrieve/v1`, OGC API - Processes) for [omnisense/omnilab](https://github.com/glitchr-omnisense/omnilab):
air quality forecasts and reanalyses, atmospheric composition, greenhouse gases, fire emissions,
solar radiation, as products and datasets; with an account's key, the requests that make their
files. The source is omnilab/cds's `CdsSource`, on the ADS's address, named `cams`.

## Installation

```sh
composer require omnilab/cams
```

It brings omnilab/cds, whose source it builds.

```php
use Omnilab\Cams\CamsSourceFactory;

$cams = (new CamsSourceFactory($httpClient))->create([
    'key' => getenv('ADS_KEY'),        // optional: an ADS account's personal access token, for requests
    'mailto' => 'support@glitchr.io',  // optional: in the User-Agent
]);
```

With the Symfony bundle, the factory is registered as `cams` when the bundle knows it; otherwise
register `CamsSourceFactory` as a service (it is autoconfigured as a `SourceFactoryInterface`).

## Credentials

None to read the catalogue (`products()`, `datasets()`, and `dataset()`, `cost()` beside the
contract). A request for a file (`submit()`, `job()`, `jobs()`) needs the personal access token
of an Atmosphere Data Store account, from its profile page on `ads.atmosphere.copernicus.eu`,
given as `key` and sent as `PRIVATE-TOKEN`; the account accepts each dataset's CAMS licence once on
the store's site before its first request. The key is read from the configuration, never written.

## Options

The factory's options (`CamsSourceFactory`, which builds omnilab/cds's `CdsSource` named `cams`), with the defaults it applies:

| Option | Type | Default | Required | Effect |
|---|---|---|---|---|
| `key` | `?string` | null | no | an ADS account's personal access token: `submit()`, `job()`, `jobs()` need it (refused without it, before any call); `open()` sends it when set |
| `mailto` | `?string` | null | no | a contact address, sent in the User-Agent |
| `user_agent` | `?string` | null | no | the whole User-Agent, in place of the one made from `mailto` |
| `throttle` | `float` | `0` | no | seconds left between two calls: the store publishes request limits, no rate |
| `base_uri` | `string` | `https://ads.atmosphere.copernicus.eu/api/` | no | the store's API, ending in `/api/` (`catalogue/v1/...` and `retrieve/v1/...` are joined to it) |

## Capabilities

`capabilities()` answers `products`, `area`, `datasets`, `jobs`, `open`; an operation it does not answer throws `NotSupportedException` before any call.

| Capability | Supported | |
|---|---|---|
| `entity` | no | a dataset is a `Product`, not an entity |
| `search` | no |  |
| `series` | no |  |
| `products` | yes | the store's datasets that cover the place and overlap the period, the most recently updated first (oldest with `ProductQuery::OLDEST`); `collections` keeps those named; paged by offset, `total` counted |
| `occurrences` | no |  |
| `datasets` | yes | the catalogue's own search (titles, descriptions, keywords), the best match first, 100 a page at most |
| `jobs` | yes | implements `JobSourceInterface`; with the `key` only |
| `open` | yes | a job's file (its `data` asset) or any `https://` address, the key sent when set; the response streamed, its status unchecked |
| `structure` | no |  |
| `cross_references` | no |  |
| `forecast` | no |  |
| `historical` | no |  |
| `area` | yes | a `BBox` or a `Point`, against each dataset's extent |
| `station` | no |  |
| `cloud_cover` | no | a dataset has no cloud cover: `maxCloudCover` is refused |

Beside the contract, `CdsSource` has `dataset(string $id): ?Product` (one dataset with its
request form: `inputs` and `variables` among its properties) and `cost(string $dataset, array
$request): array` (what a request would cost against the store's limit, and whether it is valid,
without an account).

## Which API

The Atmosphere Data Store's (`https://ads.atmosphere.copernicus.eu/api`): the same catalogue
(`/catalogue/v1`, STAC) and request API (`/retrieve/v1`, OGC API - Processes) as the Climate Data
Store's, which omnilab/cds reads. Every call, what each method answers, what a product holds and
what is refused are in [omnilab/cds's docs](https://github.com/glitchr-omnisense/omnilab-cds/blob/1.x/docs/index.md);
the source here is that `CdsSource`, named `cams`, its pages under
`https://ads.atmosphere.copernicus.eu/datasets/`.

## Why a package that builds another's source

Copying the CDS client would make two readings of one API drift apart; one configurable source
alone would leave the ADS without a name, a key and a catalogue of its own in a registry, and
without its own verification. So: one client (omnilab/cds), two factories - `cds` on the Climate
Data Store, `cams` here on the Atmosphere Data Store. A third ECMWF data store (the Early Warning
Data Store, `ewds.climate.copernicus.eu`) would be a `base_uri` on either, or a third such
package.

## The catalogue, as of 2026-10-15

Sixteen datasets: `cams-europe-air-quality-forecasts` (2023-10-10 to 2026-10-15, -25° to 45° E, 30°
to 72° N), `cams-europe-air-quality-reanalyses`,
`cams-europe-air-quality-forecasts-optimised-at-observation-sites`,
`cams-global-atmospheric-composition-forecasts` (from 2015), `cams-global-reanalysis-eac4` and its
monthly means (2003 to 2025), `cams-global-greenhouse-gas-forecasts`,
`cams-global-greenhouse-gas-inversion`, `cams-global-ghg-reanalysis-egg4` and its monthly means,
`cams-global-fire-emissions-gfas` (from 2003), `cams-global-emission-inventories`,
`cams-global-radiative-forcings` and its auxiliary variables, `cams-gridded-solar-radiation`,
`cams-solar-radiation-timeseries`.

## Example

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnilab\Cams\CamsSourceFactory;
use Omnilab\Exception\NotSupportedException;
use Omnilab\Model\Point;
use Omnilab\Source\ProductQuery;
use Omnilab\Source\Query;
use Symfony\Component\HttpClient\HttpClient;

$cams = (new CamsSourceFactory(HttpClient::create()))->create(['mailto' => 'you@example.org']);   // no key: the catalogue only

$found = $cams->datasets(new Query('air quality', limit: 3));
printf("%d datasets match \"air quality\"; the first %d:\n", $found->total, count($found));
foreach ($found as $dataset) {
    printf("  %s  %s\n", $dataset->id, $dataset->properties['title'] ?? '');
}

$day = new DateTimeImmutable('2026-10-26');
$over = $cams->products(new ProductQuery([], new Point(48.5734, 7.7521), $day, $day));
printf("%d datasets run over Strasbourg on %s\n", $over->total, $day->format('Y-m-d'));

try {
    $cams->jobs();
} catch (NotSupportedException $e) {
    echo $e->getMessage(), "\n";
}
```

```
7 datasets match "air quality"; the first 3:
  cams-europe-air-quality-forecasts  CAMS European air quality forecasts
  cams-europe-air-quality-reanalyses  CAMS European air quality reanalyses
  cams-europe-air-quality-forecasts-optimised-at-observation-sites  CAMS European air quality forecasts optimised at observation sites
6 datasets run over Strasbourg on 2026-10-26
The "cams" source does not follow requests without a key (option key: an account's personal access token).
```

(as answered on 2026-10-27, without an account; the last line is refused before any call). With
a key, a request is submitted and followed as [omnilab/cds's page](https://github.com/glitchr-omnisense/omnilab-cds/blob/1.x/docs/index.md)
shows.

```yaml
# config/packages/omnilab.yaml
omnilab:
    sources:
        cams: { factory: cams, options: { mailto: '%env(OMNILAB_MAILTO)%', key: '%env(default::ADS_KEY)%' } }
```

## Errors

Every call goes through `HttpSource`: a 404 or a 410 (and a 204) is null - an empty page for a list -; a 429 is a `RateLimitedException` (`retryAfter` from `Retry-After`); a 5xx, a time-out or a host that does not answer an `UnavailableException`; any other 4xx a `ProviderException` with its `status` and what the service said; an answer that is not JSON a `ProviderException`, a web page answered for JSON an `UnavailableException`.

Before any call (`NotSupportedException`): `submit()`, `job()`, `jobs()` without a key; `jobs()`
with a query that names datasets, a place or a period (the store lists every request together);
`products()` with `maxCloudCover`; `open()` of an address that is not `https://`. `submit()` of
an answer without a job id is a `ProviderException`; a dataset whose licence the account has not
accepted is the store's 403, a `ProviderException`; a key the store does not know its 401
(`Authentication failed: operation not allowed`). `open()` maps only an unreachable host
(`UnavailableException`): the response is returned as it is, its status the caller's to check.

## Verified, and not

Against the real store on 2026-10-15, without an account: the sixteen datasets, six of them
running over Strasbourg on 2026-10-14 (the European air quality forecasts and those optimised at
observation sites, the global composition and greenhouse gas forecasts, GFAS, the solar radiation
time series; not EAC4, which ends in 2025); the European forecasts' box, which leaves Tokyo out;
the search for "air quality" (seven matches, the European forecasts and reanalyses first); the
European forecasts' request form (28 variables, eleven models and their ensemble, ten levels,
analysis and forecast, lead times 0 to 96 hours, `grib` or `netcdf_zip`); the cost of a day of
PM2.5 over Strasbourg at 0 and 24 hours (2 of a limit of 5,000) and the store's refusal of four
analysis times in one request ("Request has not produced a valid combination of values").

**Not verified live (needs a key)**: `submit()`, `job()`, `jobs()` on the ADS - no account was
used; their reading is omnilab/cds's, built from ECMWF's client and tested there.

Again on 2026-10-27, without an account: the search for "air quality" (seven matches, the same
three first), six datasets over Strasbourg on 2026-10-26, and `jobs()` refused without a key
(the example above).

## Responsibility

This package is an independent, unofficial client: it is not affiliated with, endorsed by or
sponsored by ECMWF, the Copernicus Atmosphere Monitoring Service or the European Commission. The
store is free; its datasets carry the CAMS licence (credit "Copernicus Atmosphere Monitoring
Service"), accepted per dataset by the account, as the README's
[Terms of use](https://github.com/glitchr-omnisense/omnilab-cams#terms-of-use) says. Whoever runs
the package is responsible for complying with them and for any use made of what it returns: see
the README's [Responsibility](https://github.com/glitchr-omnisense/omnilab-cams#responsibility)
section.
