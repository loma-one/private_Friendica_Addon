<div class="generic-page-wrapper">
    <div class="panel-heading">
        <div class="pull-right picturelost-header-right">
            <label class="picturelost-toggle-label">
                <input type="checkbox" id="picturelost-toggle" onchange="showPictureLostLoading(); window.location.href='{{$base_url}}/picturelost?tab=' + (this.checked ? 'used' : 'lost');" {{if $tab == 'used'}}checked{{/if}}>
                <strong>Nur genutzte Bilder anzeigen</strong>
            </label>
        </div>
        <h1><i class="fa fa-trash-o"></i> {{$title}}</h1>
    </div>

    <p class="text-muted picturelost-hint">{{$hint}}</p>

    <!-- Dynamischer Ladeindikator -->
    <div id="picturelost-loading" class="picturelost-loading-box text-center" style="{{if $is_loading}}display: block;{{/if}}">
        <p><i class="fa fa-spinner fa-spin fa-3x fa-fw text-primary"></i></p>
        <h4 class="text-muted">Persönliche Analyse wird erstellt ...</h4>
        <small class="text-muted">Bitte einen Moment Geduld, deine Medien werden analysiert.</small>
    </div>

    <!-- Hauptinhalt (Tabelle) -->
    <div id="picturelost-content" class="table-responsive picturelost-table-wrapper" style="{{if $is_loading}}display: none;{{/if}}">
        <table class="table table-striped table-hover">
            <thead>
                <tr class="active">
                    <th class="picturelost-thumb-col">Vorschau</th>
                    <th>Datei-Details</th>
                    <th>Aktion / Link</th>
                </tr>
            </thead>
            <tbody>
            {{foreach $photos as $p}}
                <tr class="{{if $tab == 'lost'}}danger{{else}}info{{/if}}">
                    <td>
                        <a href="{{$base_url}}/photos/{{$nickname}}/image/{{$p.resource_id}}" target="_blank" title="Galerie-Ansicht zum Löschen/Verwalten öffnen">
                            <img src="{{$base_url}}/photo/{{$p.resource_id}}-3" alt="{{$p.filename}}" class="img-thumbnail picturelost-thumbnail">
                        </a>
                    </td>
                    <td class="picturelost-valign-middle">
                        <a href="{{$base_url}}/photos/{{$nickname}}/image/{{$p.resource_id}}" target="_blank" class="picturelost-filename-link">
                            {{$p.filename}}
                        </a>
                        <br>
                        <small class="text-muted">Album: {{$p.album}} | Hochgeladen: {{$p.created}}</small>
                    </td>
                    <td class="picturelost-valign-middle">
                        {{if $tab == 'lost'}}
                            <a href="{{$base_url}}/photos/{{$nickname}}/image/{{$p.resource_id}}" target="_blank" class="text-danger picturelost-action-danger">
                                <i class="fa fa-exclamation-circle"></i> Verwaister Inhalt
                            </a>
                        {{else}}
                            {{if $p.post_url}}
                                <a href="{{$p.post_url}}" target="_blank" class="btn btn-default btn-xs">
                                    <i class="fa fa-external-link"></i> Beitrag anzeigen
                                </a>
                            {{else}}
                                <span class="text-muted"><small>In Mail/Event/Profil verwendet</small></span>
                            {{/if}}
                        {{/if}}
                    </td>
                </tr>
            {{/foreach}}

            {{if !$photos && !$is_loading}}
                <tr>
                    <td colspan="3" class="text-center text-success picturelost-empty-state">
                        <i class="fa fa-check-circle fa-2x"></i><br>
                        <strong>Keine Bilder in dieser Ansicht gefunden.</strong>
                    </td>
                </tr>
            {{/if}}
            </tbody>
        </table>
    </div>

    <div class="panel-footer text-center">{{$pager nofilter}}</div>
</div>

<script>
function showPictureLostLoading() {
    var content = document.getElementById('picturelost-content');
    var loading = document.getElementById('picturelost-loading');
    if (content && loading) {
        content.style.display = 'none';
        loading.style.display = 'block';
    }
}
</script>

{{if $is_loading}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    var url = new URL(window.location.href);
    if (!url.searchParams.has('run')) {
        url.searchParams.set('run', '1');
        window.location.href = url.toString();
    }
});
</script>
{{/if}}
