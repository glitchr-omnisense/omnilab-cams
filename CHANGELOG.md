# Changelog

## [Unreleased]

## [1.0.0] - 2026-11-13

The first stable release: it installs with Composer's default minimum-stability (stable).

### Added

- Omnilab CAMS: the Copernicus Atmosphere Monitoring Service's Atmosphere Data Store - its datasets
  (European air quality forecasts and reanalyses, global atmospheric composition, greenhouse gases,
  fire emissions, solar radiation) as products over a place and a period, its catalogue's search, a
  dataset's variables and request form, what a request would cost, read without an account; with an
  account's key, a request submitted and followed until its file is the product's asset. The store
  runs the Climate Data Store's software: omnilab/cds's source, on the ADS.

### Release

- Requires `omnisense/omnilab` `^1.0` and `omnilab/cds` `^1.0`, stable versions, where it required `@dev` ones:
  `composer require omnilab/cams` installs in a project at Composer's default minimum-stability
  (stable). `extra.branch-alias` names the branch (`dev-1.x`: `1.x-dev`).
- CI: `.github/workflows/ci.yml` (glitchr-studio/ci's php-package.yml, pinned: the package
  installed alone, PHPUnit, PHPStan against its baseline, a consumer install) and `release.yml`
  (that CI on the tag, then glitchr-studio/ci's php-release.yml); `phpunit.xml.dist` runs the
  package's own tests.
- README: the install line and a first call.
