# Laravel PostGIS
Biblioteca para consultas geoespaciais em Eloquent com PostgreSQL/PostGIS: cálculo de distâncias e filtros por polígonos GeoJSON, com API moderna e tipada.

## Requisitos
- PHP **>= 8.1**
- Laravel **^9.0 || ^10.0 || ^11.0**
- PostgreSQL com extensão **PostGIS** habilitada (`CREATE EXTENSION postgis;`)

## Instalação
```bash
composer require jeorgy/laravel-postgis
```

## Compatibilização em um projeto Laravel
1. **Habilite PostGIS** na base de dados utilizada pela aplicação.
2. **Adicione a trait** ao seu modelo que possui a coluna geoespacial.
3. **Garanta o tipo da coluna** como `geography(Point, 4326)` ou equivalente na migration.
4. **Opcional**: configure o nome da coluna e a unidade de distância no modelo.

### Exemplo de modelo
```php
use Illuminate\Database\Eloquent\Model;
use Jeorgy\LaravelPostgis\Geometries\Point;
use Jeorgy\LaravelPostgis\Postgis;

class Place extends Model
{
    use Postgis;

    // Opcional: coluna personalizada e unidade
    protected $location = 'geo'; // default: location
    protected $unit = 'km';      // opções: meter (default), km, mile
}
```

### Escopos disponíveis
- **withDistance(Point|array|string $location)**: adiciona coluna `distance` à seleção usando `ST_DistanceSphere` (bindings seguros). Arrays/strings aceitam `[lat, lng]` ou `"lat,lng"`.
- **whereDistance(Point|array $location, string $operator, float|int $units)**: filtro por distância com operador (`<`, `<=`, `>`, `>=`, `=`) e valor em unidades do modelo.
- **orWhereDistance(Point|array $location, string $operator, float|int $units)**: versão OR do filtro de distância.
- **whereCovers(Polygon|object $geoJson)** / **orWhereCovers(...)**: filtra registros cobertos por polígono GeoJSON (ou `Polygon` interno), gerando `SRID=4326;POLYGON(...)` com anel fechado.

### Geometrias internas
- **Point**: `new Point($lat, $lng)`
- **Polygon**: `new Polygon([ [[$lng, $lat], ...] ])` ou `Polygon::fromGeoJson($geoJsonObject)`

### Exemplos de uso
```php
$origin = new Point(10, 20);
$places = Place::query()
    ->withDistance($origin)
    ->whereDistance($origin, '<', 5000)
    ->get();

$geoJson = json_decode('{"geometry":{"coordinates":[[[30,10],[10,20],[20,40],[40,40],[30,10]]]}}');
$inside = Place::query()
    ->whereCovers($geoJson)
    ->get();
```

## Testes
```bash
vendor/bin/phpunit
```
