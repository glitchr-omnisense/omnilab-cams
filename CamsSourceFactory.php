<?php

namespace Omnilab\Cams;

use Omnilab\Cds\CdsSource;
use Omnilab\Config;
use Omnilab\Source\SourceFactory;
use Omnilab\Source\SourceInterface;
use Symfony\Component\HttpClient\HttpClient;

/**
 * The Copernicus Atmosphere Monitoring Service's data, through its
 * Atmosphere Data Store (ads.atmosphere.copernicus.eu): the CAMS datasets
 * (air quality forecasts and reanalyses of Europe, global atmospheric
 * composition, greenhouse gases, fire emissions, solar radiation...) as
 * products, read without an account; with an account's key, the requests
 * for their files. The store runs the same software as the Climate Data
 * Store: the source is omnilab/cds's, on the ADS's address, named "cams".
 *
 *   options:
 *     key: ~                           # the personal access token of an ADS account (its profile page), for submit(), job() and jobs()
 *     mailto: 'support@glitchr.io'     # in the User-Agent
 *     throttle: 0                      # seconds between two calls
 *     base_uri: 'https://ads.atmosphere.copernicus.eu/api/'
 */
final class CamsSourceFactory extends SourceFactory
{
    protected function populate(Config $c): void
    {
        $c->defaults([
            'omnilab.factory_name' => 'cams',
            'omnilab.required_options' => [],
            'key' => null,
            'throttle' => 0.0,
            'base_uri' => CdsSource::ADS_URI,
        ]);
    }

    protected function build(Config $c): SourceInterface
    {
        return new CdsSource(
            $this->http ?? HttpClient::create(),
            'cams',
            (string) $c->get('base_uri', CdsSource::ADS_URI),
            $c->get('key'),
            ['User-Agent' => self::userAgent($c)],
            (float) $c->get('throttle'),
        );
    }
}
