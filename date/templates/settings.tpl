<div class="panel">
    <div class="section-title-wrapper">
        <h3 class="panel-title">{{$title}}</h3>
    </div>
    <div class="panel-body">
        <p class="description">{{$description}}</p>

        <div class="settings-block">
            {{include file="field_checkbox.tpl" field=$enabled}}

            <hr>

            {{include file="field_select.tpl"   field=$date_format}}

            <hr>

            {{include file="field_checkbox.tpl" field=$show_sunrise_sunset}}
            {{include file="field_input.tpl"    field=$location}}
            {{include file="field_input.tpl"    field=$api_key}}
        </div>
    </div>
</div>
