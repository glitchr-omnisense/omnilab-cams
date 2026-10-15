# omnilab/cams

The Copernicus Atmosphere Monitoring Service's Atmosphere Data Store for
[glitchr/omnilab](https://github.com/glitchr-studio/omnilab): its datasets - the European air
quality forecasts and reanalyses, the global atmospheric composition forecasts and reanalysis
(EAC4), greenhouse gases, fire emissions (GFAS), solar radiation - as products over a place and a
period, its catalogue's search, a dataset's variables and request form, what a request would cost:
all read without an account. With an account's key, a request is submitted and followed until its
file (NetCDF, GRIB, zip) is the product's asset to download.

```php
$cams = (new CamsSourceFactory($http))->create(['mailto' => 'support@glitchr.io']);

// What runs over Strasbourg today.
$page = $cams->products(new ProductQuery(place: new Point(48.5734, 7.7521), from: new \DateTimeImmutable('today'), to: new \DateTimeImmutable('today')));

$forecasts = $cams->dataset('cams-europe-air-quality-forecasts');
$forecasts->properties['variables'];        // 28: particulate_matter_2.5um, nitrogen_dioxide, ozone, birch_pollen...
$forecasts->properties['inputs']['model'];  // ensemble, chimere, emep, lotos, silam...

$request = ['variable' => ['particulate_matter_2.5um'], 'model' => ['ensemble'], 'level' => ['0'],
    'date' => ['2026-10-10/2026-10-10'], 'type' => ['forecast'], 'time' => ['00:00'],
    'leadtime_hour' => ['0', '24'], 'data_format' => 'netcdf_zip', 'area' => [49, 7, 48, 8]];
$cams->cost('cams-europe-air-quality-forecasts', $request);   // ['cost' => 2.0, 'limit' => 5000.0, 'valid' => true, ...]

// With a key: submit, then ask again until the file is there.
$cams = (new CamsSourceFactory($http))->create(['key' => getenv('ADS_KEY')]);
$job = $cams->job($cams->submit('cams-europe-air-quality-forecasts', $request)->id);
$job->asset('data')?->href;                 // once successful
```

```yaml
omnilab:
    sources:
        cams:
            factory: cams
            options:
                key: '%env(ADS_KEY)%'          # optional: an ADS account's personal access token, for requests
                mailto: 'support@glitchr.io'   # in the User-Agent
```

## The source is omnilab/cds's

The Atmosphere Data Store runs the same software as the Climate Data Store (ECMWF's data stores):
the same STAC catalogue, the same request API. This package is its factory - the name `cams`, the
ADS's address - and builds [omnilab/cds](https://github.com/glitchr-studio/omnilab-cds)'s
`CdsSource`: `products()`, `datasets()`, `dataset()`, `cost()`, `submit()`, `job()`, `jobs()`
behave as its docs say. Two packages rather than one with an option: each store has its own name
in a registry, its own key (an ADS account is not a CDS account), its own catalogue to verify and
document; one client, so that a fix to the API's reading is made once.

For the air quality as series at a point (hourly PM2.5, NO2, ozone, the European index... from
CAMS's models), without requests nor files, see omnilab/open-meteo's `air_quality` API, which
serves CAMS's data.

## Terms of use

The Atmosphere Data Store is free; its datasets carry the CAMS licence (credit "Copernicus
Atmosphere Monitoring Service"), which an account accepts once per dataset before its first
request. The request's file is the application's to read (NetCDF, GRIB, zip): nothing is decoded
here. The key is read from the configuration only, never written.

See [docs/](docs/index.md).

## Responsibility

This package is an independent, unofficial client: it is not affiliated with, endorsed by or
sponsored by ECMWF and the Copernicus Atmosphere Monitoring Service. It reaches their service on behalf of whoever runs it, and it does not
redistribute their data beyond the short excerpts recorded as test fixtures. Its MIT licence
covers this package's code only and grants no right to the service or to its data.

Whoever installs and runs this package is responsible for complying with the terms above, for
obtaining whatever they require (an account, a key, a licence or subscription, an attribution or
a citation, a rate limit to respect), and for any use made of what it returns. The authors
publish it free of charge, make no commercial use of it, and accept no liability for how it is
used (see [LICENSE](LICENSE)).

License: MIT.
