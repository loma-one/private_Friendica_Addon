<div>
    <p class="descriptive-text">{{$desc}}</p>

    <div class="form-group">
        <label for="apps_position">{{$label_pos}}</label>
        <select name="apps_position" id="apps_position" class="form-control">
            <option value="right" {{if $position == 'right'}}selected="selected"{{/if}}>Rechts (Standard)</option>
            <option value="left" {{if $position == 'left'}}selected="selected"{{/if}}>Links</option>
        </select>
    </div>

    <hr>

    <div class="row hidden-xs" style="margin-bottom: 10px; font-weight: bold;">
        <div class="col-xs-5">URL</div>
        <div class="col-xs-4">Label</div>
        <div class="col-xs-2 text-center">New Tab</div>
        <div class="col-xs-1"></div>
    </div>

    <div id="apps-links-container">
        {{foreach $links as $index => $link}}
        <div class="form-group row app-link-row" id="app_row_{{$index}}" style="margin-bottom: 8px;">
            <div class="col-xs-12 col-sm-5" style="margin-bottom: 5px;">
                <input type="url" id="apps_link_url_{{$index}}" name="apps_link_url_{{$index}}" value="{{$link.url}}" placeholder="https://..." class="form-control" />
            </div>

            <div class="col-xs-12 col-sm-4" style="margin-bottom: 5px;">
                <input type="text" id="apps_link_label_{{$index}}" name="apps_link_label_{{$index}}" value="{{$link.label}}" placeholder="Label" class="form-control" />
            </div>

            <div class="col-xs-6 col-sm-2 text-center" style="padding-top: 7px;">
                <label class="checkbox-inline" for="apps_link_new_tab_{{$index}}">
                    <input type="checkbox" name="apps_link_new_tab_{{$index}}" id="apps_link_new_tab_{{$index}}" {{if $link.open_in_new_tab}}checked="checked"{{/if}} />
                    <span class="visible-xs-inline">New Tab</span>
                </label>
            </div>

            <div class="col-xs-6 col-sm-1 text-right">
                <button type="button" class="btn btn-danger btn-sm btn-delete-row" onclick="clearAppRow({{$index}})" title="Eintrag leeren">
                    <i class="ri-delete-bin-line"></i> <span class="visible-xs-inline">Löschen</span>
                </button>
            </div>
        </div>
        {{/foreach}}
    </div>

    <div class="margin-top-10" style="margin-top: 15px;">
        <button type="button" id="btn-add-app-link" class="btn btn-default btn-sm" onclick="addNewAppRow()">
            <i class="ri-add-line"></i> Neuer Link
        </button>
    </div>
</div>

<script>
    function clearAppRow(index) {
        var urlInput = document.getElementById('apps_link_url_' + index);
        var labelInput = document.getElementById('apps_link_label_' + index);
        var checkbox = document.getElementById('apps_link_new_tab_' + index);

        if (urlInput) urlInput.value = '';
        if (labelInput) labelInput.value = '';
        if (checkbox) checkbox.checked = false;
    }

    function addNewAppRow() {
        var container = document.getElementById('apps-links-container');
        var rows = container.getElementsByClassName('app-link-row');
        var nextIndex = rows.length;

        if (nextIndex >= 10) {
            alert('Maximal 10 Links möglich.');
            return;
        }

        var newRow = document.createElement('div');
        newRow.className = 'form-group row app-link-row';
        newRow.id = 'app_row_' + nextIndex;
        newRow.style.marginBottom = '8px';

        newRow.innerHTML = `
            <div class="col-xs-12 col-sm-5" style="margin-bottom: 5px;">
                <input type="url" id="apps_link_url_${nextIndex}" name="apps_link_url_${nextIndex}" value="" placeholder="https://..." class="form-control" />
            </div>
            <div class="col-xs-12 col-sm-4" style="margin-bottom: 5px;">
                <input type="text" id="apps_link_label_${nextIndex}" name="apps_link_label_${nextIndex}" value="" placeholder="Label" class="form-control" />
            </div>
            <div class="col-xs-6 col-sm-2 text-center" style="padding-top: 7px;">
                <label class="checkbox-inline" for="apps_link_new_tab_${nextIndex}">
                    <input type="checkbox" name="apps_link_new_tab_${nextIndex}" id="apps_link_new_tab_${nextIndex}" />
                    <span class="visible-xs-inline">New Tab</span>
                </label>
            </div>
            <div class="col-xs-6 col-sm-1 text-right">
                <button type="button" class="btn btn-danger btn-sm btn-delete-row" onclick="clearAppRow(${nextIndex})" title="Eintrag leeren">
                    <i class="ri-delete-bin-line"></i> <span class="visible-xs-inline">Löschen</span>
                </button>
            </div>
        `;

        container.appendChild(newRow);

        if (container.getElementsByClassName('app-link-row').length >= 10) {
            document.getElementById('btn-add-app-link').style.display = 'none';
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        var container = document.getElementById('apps-links-container');
        if (container && container.getElementsByClassName('app-link-row').length >= 10) {
            var btn = document.getElementById('btn-add-app-link');
            if (btn) btn.style.display = 'none';
        }
    });
</script>
