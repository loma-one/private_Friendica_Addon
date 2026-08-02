<div class="generic-page-wrapper">
    <div class="panel-heading">
        <div class="pull-right" style="margin-top: 5px;">
            <!-- Checkbox-Umschalter -->
            <label style="cursor: pointer; font-weight: normal; user-select: none;">
                <input type="checkbox" onchange="window.location.href='{{$base_url}}/picturelost?tab=' + (this.checked ? 'used' : 'lost');" {{if $tab == 'used'}}checked{{/if}}>
                <strong>Nur genutzte Bilder anzeigen</strong>
            </label>
        </div>
        <h1><i class="ri-image-line"></i> {{$title}}</h1>
    </div>

    <p class="text-muted" style="padding: 15px 15px 0 15px;">{{$hint}}</p>

    <div class="table-responsive" style="padding: 15px 15px 0 15px;">
        <table class="table table-striped table-hover">
            <thead>
                <tr class="active">
                    <th width="80px">Vorschau</th>
                    <th>Datei-Details</th>
                    <th>Aktion / Link</th>
                </tr>
            </thead>
            <tbody>
            {{foreach $photos as $p}}
                <tr class="{{if $tab == 'lost'}}danger{{else}}info{{/if}}">
                    <td>
                        <!-- Bild verlinkt direkt auf die Galerie -->
                        <a href="{{$base_url}}/photos/{{$nickname}}/image/{{$p.resource_id}}" target="_blank" title="Galerie-Ansicht zum Löschen/Verwalten öffnen">
                            <img src="{{$base_url}}/photo/{{$p.resource_id}}-3" alt="{{$p.filename}}" class="img-thumbnail" style="max-width: 70px; max-height: 70px; object-fit: cover;">
                        </a>
                    </td>
                    <td style="vertical-align: middle;">
                        <!-- Klick auf Dateinamen öffnet ebenfalls die Galerie -->
                        <a href="{{$base_url}}/photos/{{$nickname}}/image/{{$p.resource_id}}" target="_blank" style="font-weight: bold; color: #333; text-decoration: underline;">
                            {{$p.filename}}
                        </a>
                        <br>
                        <small class="text-muted">Album: {{$p.album}} | Hochgeladen: {{$p.created}}</small>
                    </td>
                    <td style="vertical-align: middle;">
                        {{if $tab == 'lost'}}
                            <!-- Verwaister Inhalt jetzt ebenfalls mit Link zur Galerie -->
                            <a href="{{$base_url}}/photos/{{$nickname}}/image/{{$p.resource_id}}" target="_blank" class="text-danger" style="text-decoration: underline;">
                                <i class="ri-links-line"></i> Verwaister Inhalt
                            </a>
                        {{else}}
                            <!-- Unveränderte Darstellung bei genutzten Bildern -->
                            {{if $p.post_url}}
                                <a href="{{$p.post_url}}" target="_blank" class="btn btn-default btn-xs">
                                    <i class="ri-links-line"></i> Beitrag anzeigen
                                </a>
                            {{else}}
                                <span class="text-muted"><small>Verwendung in Beiträgen, DM, Terminen oder Profiltexten</small></span>
                            {{/if}}
                        {{/if}}
                    </td>
                </tr>
            {{/foreach}}

            {{if !$photos}}
                <tr>
                    <td colspan="3" class="text-center text-success" style="padding: 30px;">
                        <i class="ri-chat-check-line"></i><br>
                        <strong>Keine Bilder in dieser Ansicht gefunden.</strong>
                    </td>
                </tr>
            {{/if}}
            </tbody>
        </table>
    </div>

    <div class="panel-footer text-center">{{$pager nofilter}}</div>
</div>
