{*<link rel="stylesheet" href="{THEME}css/weather-icons.min.css"/>
<i class="wi wi-day-showers"></i>*}
<div class="row">
    <div class="panel panel-default col-sm-6">
        <div class="panel-heading">
            <b>Текущая погода</b>
        </div>
        <div class="panel-body">
            <img src="{current.image}" alt="{type}"/><b>{% if("{current.temperature}" > 0) %} +{current.temperature} {% else %} {current.temperature} {% endif %}°C </b><br />
            {current.weather_type}<br />
            Ветер: <img src="{current.wind_image}" alt=""/>{current.wind_speed} м/с<br />
            Атм. давление: {current.pressure} мм рт.ст.<br />
            Влажность: {current.humidity}%
        </div>
    </div>

    <div class="panel panel-default col-sm-6">
        <div class="panel-heading">
            <b>Погода на завтра</b>
        </div>
        <div class="panel-body">
            <img src="{tomorrow.day.image}" alt="{tomorrow.day.weather_type}"/><b>От
                {% if("{tomorrow.night.temp}" > 0) %} +{tomorrow.night.temp} {% else %} {tomorrow.night.temp} {% endif %}до{% if("{tomorrow.day.temp}" > 0) %} +{tomorrow.day.temp} {% else %} {tomorrow.day.temp} {% endif %}°C </b><br />
            {tomorrow.day.weather_type}<br />
            Ветер: <img src="{tomorrow.day.wind_image}" alt=""/>{tomorrow.day.wind_speed} м/с<br />
            Атм. давление: {tomorrow.day.pressure} мм рт.ст.<br />
            Влажность: {tomorrow.day.humidity}%
        </div>
    </div>
</div>