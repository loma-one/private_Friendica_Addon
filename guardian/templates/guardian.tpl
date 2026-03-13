<div id="admin-users" class="adminpage generic-page-wrapper">
    <div class="panel-heading">
        <h1>{{$title}} <span class="badge">{{$count}}</span></h1>
    </div>

    <div style="margin: 0 15px 15px 15px;">
        <div class="row">
            <div class="col-xs-6" style="padding-top: 7px;">
                <form method="get" action="{{$sort_url}}" class="form-inline">
                    <div class="checkbox">
                        <label style="cursor: pointer; {{if $only_pending}}font-weight: bold; color: #337ab7;{{/if}}">
                            <input type="hidden" name="pending" value="0">
                            <input type="checkbox" name="pending" value="1" {{if $only_pending}}checked{{/if}} onchange="this.form.submit()">
                            <span style="margin-left: 5px;">
                                {{if $only_pending}}<i class="fa fa-filter"></i> {{/if}}Nur ausstehende
                            </span>
                        </label>
                    </div>
                    <input type="hidden" name="search" value="{{$search_val}}">
                </form>
            </div>

            <div class="col-xs-6 text-right">
                <form method="get" action="{{$sort_url}}" class="form-inline">
                    <input type="hidden" name="pending" value="{{$only_pending}}">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control input-sm" placeholder="Suchen..." value="{{$search_val}}" style="width: 150px;">
                        <span class="input-group-btn">
                            <button class="btn btn-sm btn-primary" type="submit" title="Suchen">
                                <i class="fa fa-search"></i>
                            </button>
                            {{if $search_val}}
                                <a href="{{$sort_url}}?pending={{$only_pending}}" class="btn btn-sm btn-warning" title="Suche löschen">
                                    <i class="fa fa-times"></i>
                                </a>
                            {{/if}}
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="table-responsive" style="padding: 0 15px;">
        <table class="table table-striped table-hover">
            <thead>
                <tr class="active">
                    <th width="1%"></th>
                    <th><a href="{{$sort_url}}?sort=display_name&order={{$next_order}}&pending={{$only_pending}}&search={{$search_val}}">Benutzer</a></th>
                    <th>Status</th>
                    <th><a href="{{$sort_url}}?sort=register_date&order={{$next_order}}&pending={{$only_pending}}&search={{$search_val}}">Registriert</a></th>
                    <th><a href="{{$sort_url}}?sort=spam_score&order={{$next_order}}&pending={{$only_pending}}&search={{$search_val}}">Score</a></th>
                    <th class="text-left">Details / Grund</th>
                </tr>
            </thead>
            <tbody>
            {{foreach $users as $u}}
                <tr id="row-{{$u.uid}}" class="{{if $u.spam_score >= 100}}danger{{elseif $u.spam_score >= 30}}warning{{/if}}">
                    <td class="text-center" style="vertical-align: middle;">
                        <input type="checkbox" class="audit-check" data-uid="{{$u.uid}}" onclick="toggleAudit('{{$u.uid}}')">
                    </td>
                    <td style="max-width: 250px; word-break: break-all; overflow-wrap: break-word;">
                        <strong>{{$u.display_name}}</strong><br>
                        <small class="text-muted">@{{$u.nickname}}</small><br>
                        <small>{{$u.email}}</small>
                    </td>
                    <td style="vertical-align: middle;">
                        <span class="label label-{{$u.status_class}}">{{$u.status_text}}</span>
                    </td>
                    <td style="vertical-align: middle;"><small>{{$u.register_date}}</small></td>
                    <td style="vertical-align: middle;">
                        <span class="badge">{{$u.spam_score}}</span>
                    </td>
                    <td style="max-width: 200px; vertical-align: middle; text-align: left;">
                        {{foreach $u.spam_reasons as $reason}}
                            <span class="label label-default" style="font-weight:normal; margin-right:2px; display: inline-block; margin-bottom: 2px; white-space: normal; word-break: break-word; text-align: left;">{{$reason}}</span>
                        {{/foreach}}
                    </td>
                </tr>
            {{/foreach}}
            </tbody>
        </table>
    </div>

    <div class="panel-footer text-center">
        {{$pager nofilter}}
    </div>

    <div style="margin: 20px 15px;">
        {{$hilfe nofilter}}
    </div>
</div>

<script>
    function toggleAudit(uid) {
        const row = document.getElementById('row-' + uid);
        row.classList.toggle('row-checked');
        localStorage.setItem('guardian_checked_' + uid, row.classList.contains('row-checked'));
    }
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.audit-check').forEach(function(box) {
            const uid = box.getAttribute('data-uid');
            if (localStorage.getItem('guardian_checked_' + uid) === 'true') {
                box.checked = true;
                document.getElementById('row-' + uid).classList.add('row-checked');
            }
        });
    });
</script>

<style>
    .row-checked { opacity: 0.4; filter: grayscale(1); background-color: #eee !important; }
    .row-checked strong { text-decoration: line-through; }
    /* Sicherstellen, dass Text in Zellen links bleibt */
    .table > tbody > tr > td { text-align: left; }
</style>
