<div class="settings-block">
    <h3>{{$title}}</h3>
    <p class="description">{{$description}}</p>

    {{include file="field_checkbox.tpl" field=$enabled}}
    {{include file="field_select.tpl" field=$months}}
    {{include file="field_select.tpl" field=$visibility}}
    {{include file="field_input.tpl" field=$recipient}}
    {{include file="field_textarea.tpl" field=$message}}
</div>
