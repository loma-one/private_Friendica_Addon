<div id="admin-users" class="adminpage generic-page-wrapper">
    <h1>{{$title}} ({{$count}})</h1>

    <table class="table table-hover" id="guardian-table">
        <thead>
            <tr>
                <th width="1%"><i class="fa fa-eye" title="Geprüft"></i></th>
                <th width="5%">
                    <a href="{{$sort_url}}?sort=uid&order={{$next_order}}">ID {{if $current_sort == 'uid'}}<i class="fa fa-sort-{{if $next_order == 'asc'}}desc{{else}}asc{{/if}}"></i>{{/if}}</a>
                </th>
                <th>
                    <a href="{{$sort_url}}?sort=display_name&order={{$next_order}}">Benutzer {{if $current_sort == 'display_name'}}<i class="fa fa-sort-{{if $next_order == 'asc'}}desc{{else}}asc{{/if}}"></i>{{/if}}</a>
                </th>
                <th>
                    <a href="{{$sort_url}}?sort=register_date&order={{$next_order}}">Registriert {{if $current_sort == 'register_date'}}<i class="fa fa-sort-{{if $next_order == 'asc'}}desc{{else}}asc{{/if}}"></i>{{/if}}</a>
                </th>
                <th width="10%">
                    <a href="{{$sort_url}}?sort=spam_score&order={{$next_order}}">Score {{if $current_sort == 'spam_score'}}<i class="fa fa-sort-{{if $next_order == 'asc'}}desc{{else}}asc{{/if}}"></i>{{/if}}</a>
                </th>
                <th>Analyse-Details</th>
            </tr>
        </thead>
        <tbody>
        {{foreach $users as $u}}
            <tr id="row-{{$u.uid}}" class="guardian-row {{if $u.spam_score > 40}}danger-bg{{/if}}">
                <td>
                    <input type="checkbox" class="audit-check" data-uid="{{$u.uid}}" onclick="toggleAudit('{{$u.uid}}')">
                </td>
                <td>{{$u.uid}}</td>
                <td>
                    <strong>{{$u.display_name}}</strong>
                    {{if $u.nickname}}<small class="text-muted">(@{{$u.nickname}})</small>{{/if}}<br>
                    <small style="color: #666;">{{$u.email}}</small>
                </td>
                <td><small>{{$u.register_date}}</small></td>
                <td>
                    <span class="badge {{if $u.spam_score >= 100}}alert-danger{{elseif $u.spam_score > 40}}alert-warning{{else}}alert-info{{/if}}">
                        {{$u.spam_score}}
                    </span>
                </td>
                <td>
                    {{foreach $u.spam_reasons as $reason}}
                        <span class="label label-default" style="font-weight: normal; margin-right: 3px; background-color: #777;">{{$reason}}</span>
                    {{/foreach}}
                </td>
            </tr>
        {{/foreach}}
        </tbody>
    </table>

    <style>
        .danger-bg { background-color: #fff1f1 !important; }
        .row-checked { opacity: 0.4; filter: grayscale(1); background-color: #f0f0f0 !important; }
        .row-checked strong { text-decoration: line-through; }
    </style>

    <script>
        function toggleAudit(uid) {
            const row = document.getElementById('row-' + uid);
            row.classList.toggle('row-checked');
            // Speichert den Status im Browser, damit er beim Neuladen bleibt
            const checked = row.classList.contains('row-checked');
            localStorage.setItem('guardian_audit_' + uid, checked);
        }

        // Beim Laden den Status aus dem Speicher wiederherstellen
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.audit-check').forEach(function(box) {
                const uid = box.getAttribute('data-uid');
                if (localStorage.getItem('guardian_audit_' + uid) === 'true') {
                    box.checked = true;
                    document.getElementById('row-' + uid).classList.add('row-checked');
                }
            });
        });
    </script>

    <div class="panel-footer">
        {{$pager nofilter}}
    </div>
    {{$hilfe nofilter}}
</div>
