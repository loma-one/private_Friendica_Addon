<div id="picturelost-settings-content">
    <p>Hier kannst du die Suche nach verwaisten Bildern für deinen Account aktivieren.</p>

    {{include file="field_checkbox.tpl" field=$enabled}}

    {{if $is_active}}
    <div class="picturelost-settings-btn">
        <a href="{{$app_url}}"
           class="btn btn-sm btn-primary"
           onclick="var c=document.getElementById('picturelost-settings-content'), l=document.getElementById('picturelost-settings-loading'); if(c&&l){c.style.display='none'; l.style.display='block';}">
            <i class="fa fa-arrow-right"></i> Direkt zur PictureLost-Analyse wechseln
        </a>
    </div>
    {{/if}}

    <input type="hidden" name="picturelost-submit" value="1">
</div>

<div id="picturelost-settings-loading" class="picturelost-loading-box text-center">
    <p><i class="fa fa-spinner fa-spin fa-3x fa-fw text-primary"></i></p>
    <h4 class="text-muted">Persönliche Analyse wird erstellt ...</h4>
    <small class="text-muted">Bitte einen Moment Geduld, deine Medien werden analysiert.</small>
</div>
