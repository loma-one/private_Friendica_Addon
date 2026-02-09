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

    <div style="margin-bottom:5px; font-weight:bold; display: flex;">
        <span style="width:45%; margin-right:5px;">URL</span>
        <span style="width:35%; margin-right:5px;">Label</span>
        <span>New Tab</span>
    </div>

    {{foreach $links as $index => $link}}
    <div class="form-group" style="margin-bottom:5px; display: flex; align-items: center;">
        <input type="url" name="apps_link_url_{{$index}}" value="{{$link.url}}" placeholder="https://..."
               class="form-control" style="width:45%; margin-right:5px;" />

        <input type="text" name="apps_link_label_{{$index}}" value="{{$link.label}}" placeholder="Label"
               class="form-control" style="width:40%; margin-right:5px;" />

        <input type="checkbox" name="apps_link_new_tab_{{$index}}" id="apps_link_new_tab_{{$index}}"
               {{if $link.open_in_new_tab}}checked="checked"{{/if}} />
    </div>
    {{/foreach}}
</div>
