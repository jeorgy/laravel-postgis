---
description: Repository Information Overview
alwaysApply: true
---

# Laravel PostGIS Information

## Summary
Laravel PostGIS is a PHP package that augments Laravel models with geospatial querying helpers for PostgreSQL/PostGIS. It is a fork of DigitalCloud Laravel-Postgis and focuses on calculating distances between coordinates and filtering records inside polygons or circles. The package exposes Eloquent scopes for distance calculations and GeoJSON polygon coverage checks, targeting Laravel applications that need spatial lookups without managing raw PostGIS SQL in application code. The repository is a small Composer package rather than a full Laravel application; it contains a single trait that can be mixed into user models to enable PostGIS-backed queries.

## Structure
- **src/**: Package source code with the `Postgis` trait implementing query scopes for distance and polygon coverage.
- **composer.json / composer.lock**: Composer package metadata, constraints, and locked dependency versions; autoload maps `Jeorgy\\LaravelPostgis\\` to `src/`.
- **README.md**: Usage instructions demonstrating trait inclusion and query examples for distance and polygon filters.
- **.gitignore**: Excludes `vendor/` and IDE settings.
- **.zencoder/** and **.zenflow/**: Workflow metadata directories unrelated to runtime code.

## Language & Runtime
**Language**: PHP
**Version constraint**: PHP >=7.1.3 (composer.json); locked dependencies are compatible with PHP 7.2.5+ due to Laravel 7.30.4.
**Framework compatibility**: Laravel framework ^6.0 || ^7.0 || ^8.0 (composer.json); lockfile resolves to Laravel 7.30.4.
**Build system**: Composer package with PSR-4 autoloading.
**Package manager**: Composer (preferred-install: dist; minimum-stability: dev with prefer-stable: true).

## Dependencies
**Main Dependencies (from composer.json and composer.lock)**:
- **laravel/framework**: v7.30.4 (core framework; brings Illuminate components and Symfony 5.x stack).
- **mstaack/laravel-postgis**: v5.0 (provides PostGIS geometry types, Eloquent extensions, and service provider bindings).
- **bosnadev/database**: v0.20 (extends Eloquent for PostgreSQL/PostGIS support).
- **doctrine/dbal**: v2.13.0 (DBAL used by bosnadev and Laravel migrations for advanced schema operations).
- **geo-io/wkb-parser**, **jmikola/geojson**: spatial parsing utilities pulled by laravel-postgis.
- **monolog/monolog**: v2.2.0 (logging, pulled by Laravel core).
- **vlucas/phpdotenv**, **symfony/***, **nesbot/carbon**, **ramsey/uuid**: foundational Laravel dependencies (versions pinned via Laravel 7.30.4 transitive requirements).

**Development Dependencies**:
- None declared in composer.json; no project-level dev tools are configured in this repository. Upstream packages reference phpunit/mockery for their own dev cycles, but they are not required here.

**Autoloading**:
- PSR-4: `Jeorgy\\LaravelPostgis\\` → `src/` (package exports the `Postgis` trait).

## Build & Installation
```bash
# Install package dependencies for development/contribution
composer install

# Typical consumer install inside a Laravel app
composer require jeorgy/laravel-postgis
```
Notes:
- The package targets Laravel 6–8; ensure the host application satisfies the PHP (>=7.1.3) and Laravel version constraints.
- Composer config prefers dist installs; minimum-stability is `dev` with `prefer-stable` enabled, so stable versions are chosen when available.

## Main Files & Entry Points
- **[./src/Postgis.php](./src/Postgis.php)**: Central trait mixed into Eloquent models. Provides:
  - **scopeWithDistance(Builder $query, Point $location|null)**: Adds a `distance` select column using `ST_Distance` against the model’s `location` column (default) or an overridden `$location` property. Division factor adapts to `$unit` property (`meter` default, `km` divides by 1000, `mile` divides by 0.000621371).
  - **scopeWhereDistance / scopeOrWhereDistance**: Raw `WHERE`/`OR WHERE` distance comparisons using `ST_Distance` against a provided `Point` or coordinate pair.
  - **scopeWhereCovers / scopeOrWhereCovers**: Filters records whose geometry is covered by a GeoJSON polygon; builds `ST_Covers(ST_GeographyFromText(...), location_column)` clauses, accepting `Polygon` instances or GeoJSON-like objects.
  - **getLocationColumn()**: Resolves the qualified geometry column name, defaulting to `table.location` and honoring a `$location` property override on the model.
  - **getDivisionFactor()**: Maps `$unit` overrides to appropriate distance divisors.
- **README.md**: Demonstrates trait usage, model setup, and example distance/polygon queries for consumer Laravel apps.

## Configuration & Integration Notes
- The package does not register Laravel service providers directly; consumers simply `use Postgis;` in their Eloquent models. All database connection, PostGIS extension enabling, and schema definitions remain responsibilities of the host Laravel application.
- Spatial column naming: defaults to `location`; override with a `$location` property in the model.
- Distance units: defaults to meters; override `$unit` with `km` or `mile` to change division factor in distance calculations.
- Geometry handling: Polygon helper accepts either `MStaack\LaravelPostgis\Geometries\Polygon` or GeoJSON-like objects (expects `geometry->coordinates`). Coordinates are flattened into WKT POLYGON text for `ST_GeographyFromText`.
- Query column selection: Scopes ensure base table columns are selected when the query builder has no explicit column list before adding computed columns.

## Testing
- No tests or test configurations are present in the repository (`tests/` directory and phpunit config are absent). Consumers should validate functionality within their Laravel applications, ideally with feature tests that cover PostGIS-enabled queries.
- Upstream dependencies (Laravel, laravel-postgis) include their own test suites; this package relies on their stability rather than defining local tests.
