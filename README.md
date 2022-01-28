# Laravel PostGIS
This package implemented to calculate or check the distance between the point and other points from the database and also retrieve points in an area, be it an arbitrary polygon or a circle, and this is a fork of [DigitalCloud Laravel-Posgis](https://github.com/DigitalCloud/laravel-postgis/blob/master/src/Postgis.php).

Implemented function to search for points in a geoJSON polygon: whereCovers

## Installation

[PHP](https://php.net) >=7.1.3 and [Laravel](http://laravel.com) ^7.x are required.

the package used [Laravel postgis extension](https://github.com/njbarrett/laravel-postgis) to deal with postgres database points in laravel,
so if need more details or how to enable postgis extension in php see previous link.

To get the latest version of Laravel PostGIS, simply require the project using [Composer](https://getcomposer.org):

```bash
composer require jeorgy/laravel-postgis
```

## Usage

1 . First of all use `Postgis` trait in your model
```PHP
<?php

namespace App;

use Jeorgy\LaravelPostgis\Postgis;
use Illuminate\Database\Eloquent\Model;

class UserLocation extends Model
{
    use Postgis;
}
```

2 . By default package assume that the name of the point column is `location` if you want to change it override `location` variable on your model
```PHP
protected $location = "my_column";
```

3 . Also By default package assume that the unit of distance is `meter` if you want to change it override `unit` variable on your model
```PHP
 protected $unit = "km"; //units avialble [mile, km, meter]
```

### Functions

#### 1. withDistance
get the distance between point and other points
```PHP
UserLocation::withDistance(new Point($latitude,$longitude))
            ->with("user")
            ->whereIn("user_id", $users)
            ->get();
```

#### 2. whereDistance and orWhereDistance
check the distance between a point and other points in database
```PHP
       UserLocation::whereDistance(new Point($latitude,$longitude), ">", 50)
            ->with("user")
            ->whereIn("user_id", $users)
            ->get();
```


#### 3. whereCovers and orWhereCovers
get points inside a geoJSON polygon
```PHP
       UserLocation::whereCovers($geoJson)
            ->with("user")
            ->get();
```
