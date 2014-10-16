<?php
namespace NextFW\Module;

use NextFW\Engine as Engine;

class Weather {
    function getCityList()
    {
        $xml = new Engine\pQuery();
        $list = $xml->file_get_html("http://weather.yandex.ru/static/cities.xml");
        $arr = [];
        foreach ($list->find('city') as $value) {
            $country = $value->country;
            $arr[$country][] = [
                "id" => $value->id,
                "region_id" => $value->region,
                "region_name" => $value->part,
                "city_name" => $value->plaintext
            ];
        }
        return $arr;
    }
    function getWeather($cityId)
    {
        $xml = new Engine\pQuery();
        $weather = $xml->file_get_html("http://export.yandex.ru/weather-ng/forecasts/{$cityId}.xml");
        $arr = [
            "city" => $weather->find('forecast',0)->city,
            "country" => $weather->find('forecast',0)->country,
        ];
        foreach ($weather->find('fact') as $value) {
            $arr['current_weather'] = [
                "station" => $value->find('station[lang=ru]',0)->plaintext,
                "temperature" => $value->find('temperature',0)->plaintext,
                "weather_type" => $value->find('weather_type',0)->plaintext,
                "weather_type_short" => $value->find('weather_type_short',0)->plaintext,
                "wind_speed" => $value->find('wind_speed',0)->plaintext,
                "wind_direction" => $value->find('wind_direction',0)->plaintext,
                "wind_image" => "http://yandex.st/weather/1.2.77/i/wind/".$value->find('wind_direction',0)->plaintext.".gif",
                "pressure" => $value->find('pressure',0)->plaintext,
                "humidity" => $value->find('humidity',0)->plaintext,
                "daytime" => $value->find('daytime',0)->plaintext,
                "image" => "http://img.yandex.net/i/wiz".$value->find('image',0)->plaintext.".png",
            ];
        }
        foreach($weather->find('day') as $value)
        {
            $date = $value->date;
            $arr[$date] = [
                "sunrise" => $value->find('sunrise',0)->plaintext,
                "sunset" => $value->find('sunset',0)->plaintext,
            ];
            foreach ($value->find('day_part') as $daypart) {
                $type = $daypart->type;
                if($daypart->find('temperature_from',0) != null) {
                    $arr[$date][$type] = [
                        "temp_from" => $daypart->find('temperature_from',0)->plaintext,
                        "temp_to" => $daypart->find('temperature_to',0)->plaintext,
                        "weather_type" => $daypart->find('weather_type',0)->plaintext,
                        "weather_type_short" => $daypart->find('weather_type_short',0)->plaintext,
                        "wind_speed" => $daypart->find('wind_speed',0)->plaintext,
                        "wind_direction" => $daypart->find('wind_direction',0)->plaintext,
                        "wind_image" => "http://yandex.st/weather/1.2.77/i/wind/".$daypart->find('wind_direction',0)->plaintext.".gif",
                        "pressure" => $daypart->find('pressure',0)->plaintext,
                        "humidity" => $daypart->find('humidity',0)->plaintext,
                        "image" => "http://img.yandex.net/i/wiz".$daypart->find('image',0)->plaintext.".png",
                    ];
                } else {
                    $arr[$date][$type] = [
                        "temp" => $daypart->find('temperature',0)->plaintext,
                        "weather_type" => $daypart->find('weather_type',0)->plaintext,
                        "weather_type_short" => $daypart->find('weather_type_short',0)->plaintext,
                        "wind_speed" => $daypart->find('wind_speed',0)->plaintext,
                        "wind_direction" => $daypart->find('wind_direction',0)->plaintext,
                        "wind_image" => "http://yandex.st/weather/1.2.77/i/wind/".$daypart->find('wind_direction',0)->plaintext.".gif",
                        "pressure" => $daypart->find('pressure',0)->plaintext,
                        "humidity" => $daypart->find('humidity',0)->plaintext,
                        "image" => "http://img.yandex.net/i/wiz".$daypart->find('image',0)->plaintext.".png",
                    ];
                }
            }
        }
        return $arr;
    }
} 