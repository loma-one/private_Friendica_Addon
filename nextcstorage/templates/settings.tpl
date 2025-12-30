<div class="settings-block">
    <h3 class="settings-header">{{$header}}</h3>
    <p class="descriptive-text">{{$description}}</p>

    {{include file="field_input.tpl" field=$cloud_id}}
    {{include file="field_password.tpl" field=$app_password}}
    {{include file="field_input.tpl" field=$path}}

    <hr>
    {{include file="field_checkbox.tpl" field=$disconnect}}

    <div class="panel panel-default" style="margin-top: 20px;">
        <div class="panel-heading">
            <h4 class="panel-title">{{$status_label}}</h4>
        </div>
        <div class="panel-body">
            <span class="label {{if $active}}label-success{{else}}label-warning{{/if}}">
                {{$status}}
            </span>
        </div>
    </div>
</div>
