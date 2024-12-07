<div>
    <label for="apps_link_url_{{$index}}">URL:</label>
    <input type="url" name="apps_link_url_{{$index}}" value="{{$url}}" id="apps_link_url_{{$index}}" />
    <label for="apps_link_label_{{$index}}">Label:</label>
    <input type="text" name="apps_link_label_{{$index}}" value="{{$label}}" id="apps_link_label_{{$index}}" />
    <label for="apps_link_new_tab_{{$index}}">New Tab:</label>
    <input type="checkbox" name="apps_link_new_tab_{{$index}}" id="apps_link_new_tab_{{$index}}" {{ $openInNewTab ? 'checked' : '' }} />
</div>
