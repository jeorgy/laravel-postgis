<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Jeorgy\LaravelPostgis\Geometries\Point;
use Jeorgy\LaravelPostgis\Postgis;
use Orchestra\Testbench\TestCase;

class PostgisTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    public function testWithDistanceAddsDistanceColumn(): void
    {
        $query = Place::query()->withDistance(new Point(10, 20));
        $sql = $query->toSql();

        $this->assertStringContainsString('ST_DistanceSphere(places.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)) / 1 as distance', $sql);
        $this->assertSame([20.0, 10.0], $query->getBindings());
    }

    public function testWithDistanceDefaultsToZeroWhenLocationMissing(): void
    {
        $sql = Place::query()->withDistance(null)->toSql();

        $this->assertStringContainsString('0 as distance', $sql);
    }

    public function testWithDistanceRespectsKilometerUnit(): void
    {
        $sql = PlaceKilometer::query()->withDistance(new Point(1, 2))->toSql();

        $this->assertStringContainsString('/ 1000 as distance', $sql);
    }

    public function testWithDistanceRespectsMileUnit(): void
    {
        $sql = PlaceMile::query()->withDistance(new Point(3, 4))->toSql();

        $this->assertStringContainsString('/ 1609.344 as distance', $sql);
    }

    public function testWhereDistanceUsesArrayOrder(): void
    {
        $query = Place::query()->whereDistance([1, 2], '<', 500);
        $sql = $query->toSql();

        $this->assertStringContainsString('where ST_DistanceSphere(places.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)) < ?', $sql);
        $this->assertSame([2.0, 1.0, 500], $query->getBindings());
    }

    public function testOrWhereDistanceUsesPoint(): void
    {
        $query = Place::query()
            ->where('id', '>', 0)
            ->orWhereDistance(new Point(5, 6), '>=', 150);

        $sql = $query->toSql();

        $this->assertStringContainsString('or (ST_DistanceSphere(places.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)) >= ?)', $sql);
        $this->assertSame([0, 6.0, 5.0, 150], $query->getBindings());
    }

    public function testWhereCoversBuildsPolygonFromGeoJson(): void
    {
        $query = Place::query()->whereCovers($this->geoJsonPolygon());
        $sql = $query->toSql();

        $this->assertStringContainsString('ST_Covers(ST_GeogFromText(?), places.location)', $sql);
        $this->assertSame(['SRID=4326;POLYGON((30 10, 10 20, 20 40, 40 40, 30 10))'], $query->getBindings());
    }

    public function testOrWhereCoversBuildsPolygonFromGeoJson(): void
    {
        $query = Place::query()
            ->where('id', '>', 0)
            ->orWhereCovers($this->geoJsonPolygon());

        $sql = $query->toSql();

        $this->assertStringContainsString('or (ST_Covers(ST_GeogFromText(?), places.location))', $sql);
        $this->assertSame([0, 'SRID=4326;POLYGON((30 10, 10 20, 20 40, 40 40, 30 10))'], $query->getBindings());
    }

    public function testWithDistanceUsesCustomLocationColumn(): void
    {
        $sql = PlaceCustomLocation::query()->withDistance(new Point(7, 8))->toSql();

        $this->assertStringContainsString('ST_DistanceSphere(places.geo, ST_SetSRID(ST_MakePoint(?, ?), 4326)) / 1 as distance', $sql);
    }

    private function geoJsonPolygon(): object
    {
        return json_decode('{"geometry":{"coordinates":[[[30,10],[10,20],[20,40],[40,40],[30,10]]]}}');
    }
}

class Place extends Model
{
    use Postgis;

    protected $table = 'places';
    protected $guarded = [];
    public $timestamps = false;
    protected $connection = 'sqlite';
}

class PlaceCustomLocation extends Place
{
    protected $location = 'geo';
}

class PlaceKilometer extends Place
{
    protected $unit = 'km';
}

class PlaceMile extends Place
{
    protected $unit = 'mile';
}
