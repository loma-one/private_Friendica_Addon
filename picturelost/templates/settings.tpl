<p>Hier kannst du die Suche nach verwaisten Bildern für deinen Account aktivieren.</p>

{{include file="field_checkbox.tpl" field=$enabled}}

{{if $is_active}}
<div style="margin-top: 15px; margin-bottom: 15px;">
    <a href="{{$app_url}}" class="btn btn-sm btn-primary" target="">
        <i class="fa fa-arrow-right"></i> Direkt zur PictureLost-Analyse wechseln
    </a>
</div>
{{/if}}

<input type="hidden" name="picturelost-submit" value="1">
