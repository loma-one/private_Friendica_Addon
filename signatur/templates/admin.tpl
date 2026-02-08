<form action="{{$baseurl}}/addon/date/settings" method="post">
    <div class="form-group">
        <span>{{$l10n->t('API Key:')}}</span><br>
        <span>{{$l10n->t('You can obtain an API key from the <a href="https://opencagedata.com" target="_blank">OpenCage Geocoding API website</a>.')}}</span><br>
        <input type="text" name="api_key" value="{{$api_key}}" style="width: 30ch;">
    </div>
    <div class="form-group">
        <input type="checkbox" name="show_sunrise_sunset" value="1" {{ $show_sunrise_sunset ? 'checked="checked"' : '' }}>
        <span>{{$l10n->t('Show Sunrise and Sunset:')}}</span>
    </div>
    <div class="form-group">
        <span>{{$l10n->t('Location (Country code, Postal code = DE,10115):')}}</span><br>
        <input type="text" name="location" value="{{$location}}" style="width: 15ch;">
    </div>
    <div class="submit"><input type="submit" name="date-submit" value="{{$submit}}"></div>
</form>
