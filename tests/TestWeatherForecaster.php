<?php

namespace Clair\Ai\Tests;

class TestWeatherForecaster
{

    public function __construct(
        public readonly string $name
    )
    {
    }

    /**
     * 現在の天気を取得する
     * @param string $location 日本語の文字列で場所を示す
     * @param string $unit
     * @return string
     */
    public function getCurrentWeather(string $location, string $unit): string
    {
        $str = $this->name . ":" . $location . "は晴れです";
        echo $str;
        return $str;
    }

    /**
     * @param string $location
     * @param string $format
     * @return string
     */
    public static function getCurrentTemperature(string $location, string $format = "celsius"): string
    {
        $str = $location . "::24度";
        echo $str;
        return $str;
    }

    public static function getChanceOfRain(string $location, string $when): string
    {
        $str = $when . "の" . $location . "の降水確率は20％です";
        echo $str;
        return $str;
    }
}
