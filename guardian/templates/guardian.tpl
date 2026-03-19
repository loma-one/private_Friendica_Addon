<div id="admin-users" class="adminpage generic-page-wrapper">
    <div class="panel-heading">
        <h1><i class="fa fa-shield"></i> {{$title}} <span class="badge">{{$count}}</span></h1>
    </div>

    <div style="margin: 0 15px 15px 15px;">
        <div class="row">
            <div class="col-xs-6">
                <form method="get" action="{{$sort_url}}" class="form-inline">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-filter"></i> Ansicht</span>
                        <select name="view" class="form-control input-sm" onchange="this.form.submit()">
                            <option value="48h" {{if $view_mode == '48h'}}selected{{/if}}>Letzte 48 Stunden</option>
                            <option value="spam" {{if $view_mode == 'spam'}}selected{{/if}}>Spam-Verdacht</option>
                            <option value="pending" {{if $view_mode == 'pending'}}selected{{/if}}>Nur ausstehende</option>
                            <option value="all" {{if $view_mode == 'all'}}selected{{/if}}>Alle Accounts</option>
                        </select>
                    </div>
                    <input type="hidden" name="search" value="{{$search_val}}">
                </form>
            </div>
            <div class="col-xs-6 text-right">
                <form method="get" action="{{$sort_url}}" class="form-inline">
                    <input type="hidden" name="view" value="{{$view_mode}}">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control input-sm" placeholder="Suchen..." value="{{$search_val}}" style="width: 200px;">
                        <span class="input-group-btn">
                            <button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-search"></i></button>
                            {{if $search_val}}<a href="{{$sort_url}}?view={{$view_mode}}" class="btn btn-sm btn-warning"><i class="fa fa-times"></i></a>{{/if}}
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
                    <th><a href="{{$sort_url}}?sort=display_name&order={{$next_order}}&view={{$view_mode}}&search={{$search_val}}">Benutzer</a></th>
                    <th>Status</th>
                    <th><a href="{{$sort_url}}?sort=register_date&order={{$next_order}}&view={{$view_mode}}&search={{$search_val}}">Registriert</a></th>
                    <th><a href="{{$sort_url}}?sort=spam_score&order={{$next_order}}&view={{$view_mode}}&search={{$search_val}}">Score</a></th>
                    <th class="text-left">Details / Grund</th>
                </tr>
            </thead>
            <tbody>
            {{foreach $users as $u}}
                <tr id="row-{{$u.uid}}" class="{{if $u.spam_score >= 100}}danger{{elseif $u.spam_score >= 30}}warning{{/if}}">
                    <td class="text-center" style="vertical-align: middle;">
                        <input type="checkbox" class="audit-check" data-uid="{{$u.uid}}" onclick="toggleAudit('{{$u.uid}}')">
                    </td>
                    <td><strong>{{$u.display_name}}</strong><br><small>@{{$u.nickname}}</small><br><small>{{$u.email}}</small></td>
                    <td style="vertical-align: middle;"><span class="label label-{{$u.status_class}}">{{$u.status_text}}</span></td>
                    <td style="vertical-align: middle;"><small>{{$u.register_date}}</small></td>
                    <td style="vertical-align: middle;"><span class="badge">{{$u.spam_score}}</span></td>
                    <td style="max-width: 200px; text-align: left;">
                        {{foreach $u.spam_reasons as $reason}}<span class="label label-default" style="font-weight:normal; margin-right:2px; display: inline-block; margin-bottom: 2px;">{{$reason}}</span>{{/foreach}}
                    </td>
                </tr>
            {{/foreach}}
            </tbody>
        </table>
    </div>
    <div class="panel-footer text-center">{{$pager nofilter}}</div>
    <div style="margin: 20px 15px;">{{$hilfe nofilter}}</div>
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
    .table > tbody > tr > td { text-align: left; vertical-align: middle; }
</style>
