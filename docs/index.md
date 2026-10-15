# omnilab/cams

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

## Which API

The Atmosphere Data Store's (`https://ads.atmosphere.copernicus.eu/api`): the same catalogue
(`/catalogue/v1`, STAC) and request API (`/retrieve/v1`, OGC API - Processes) as the Climate Data
Store's, which omnilab/cds reads. Every call, what each method answers, what a product holds and
what is refused are in [omnilab/cds's docs](https://github.com/glitchr-studio/omnilab-cds/blob/1.x/docs/index.md);
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
