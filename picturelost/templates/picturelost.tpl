<div class="generic-page-wrapper">
    <div class="panel-heading">
        <h1><i class="fa fa-trash-o"></i> {{$title}} <span class="badge label-danger">{{$count}}</span></h1>
    </div>

    <div class="table-responsive" style="padding: 15px 15px 0 15px;">
        <table class="table table-striped table-hover">
            <thead>
                <tr class="active">
                    <th width="80px">Vorschau</th>
                    <th>Datei-Details (Klick auf Titel öffnet Galerie zum Löschen)</th>
                </tr>
            </thead>
            <tbody>
            {{foreach $photos as $p}}
                <tr class="danger">
                    <td>
                        <img src="{{$base_url}}/photo/{{$p.resource_id}}-3" alt="{{$p.filename}}" class="img-thumbnail" style="max-width: 70px; max-height: 70px; object-fit: cover;">
                    </td>
                    <td style="vertical-align: middle;">
                        <!-- Klick auf den Titel öffnet die Galerie-Löschseite in einem neuen Tab -->
                        <a href="{{$base_url}}/photos/{{$nickname}}/image/{{$p.resource_id}}" target="_blank" style="font-weight: bold; color: #d9534f; text-decoration: underline;">
                            <i class="fa fa-trash"></i> {{$p.filename}}
                        </a>
                        <br>
                        <small class="text-muted">Album: {{$p.album}} | Hochgeladen: {{$p.created}}</small>
                    </td>
                </tr>
            {{/foreach}}
            {{if !$photos}}
                <tr>
                    <td colspan="2" class="text-center text-success" style="padding: 30px;">
                        <i class="fa fa-check-circle fa-2x"></i><br>
                        <strong>Keine verwaisten Bilder gefunden. Deine Galerie ist absolut sauber!</strong>
                    </td>
                </tr>
            {{/if}}
            </tbody>
        </table>
    </div>
    <div class="panel-footer text-center">{{$pager nofilter}}</div>
</div>
