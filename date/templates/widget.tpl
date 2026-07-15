<div id="date-network" class="widget">
    <div class="pull-left">
        <ul class="date-details">
            <li>
                <strong>{{$weekday}}, {{$currentDate}}</strong>
            </li>

            <li style="margin-top: 6px;">
                <img src="{{$baseurl}}/addon/date/icon/calendar.png" width="20" height="20" alt="Calendar" style="margin-right: 8px; vertical-align: middle;">
                {{$week_label}} {{$weekNumber}}
            </li>

            {{if $showTemperature && $temperature !== 'N/A'}}
            <li style="margin-top: 6px;">
                <span class="glyphicon glyphicon-cloud" style="margin-right: 8px; font-size: 16px; vertical-align: middle;"></span>
                {{$temp_label}}: {{$temperature}} °C
            </li>
            {{/if}}

            {{if $showSunriseSunset && $sunrise !== 'N/A' && $sunset !== 'N/A'}}
            <li style="margin-top: 6px;">
                <img src="{{$baseurl}}/addon/date/icon/sunrise.png" width="20" height="20" alt="Sunrise" style="margin-right: 8px; vertical-align: middle;">
                {{$sunrise_label}} {{$sunrise}} h
            </li>
            <li style="margin-top: 6px;">
                <img src="{{$baseurl}}/addon/date/icon/sunset.png" width="20" height="20" alt="Sunset" style="margin-right: 8px; vertical-align: middle;">
                {{$sunset_label}} {{$sunset}} h
            </li>
            {{/if}}
        </ul>
    </div>
    <div class="clear"></div>
</div>
<div class="clear"></div>
