<div class="settings-block">
    <h3>{{$title}}</h3>
    <p class="description">{{$description}}</p>

    {{include file="field_input.tpl" field=$admin_contact}}

    <div class="submit">
        <input type="submit" name="page_site" value="{{$submit}}" />
    </div>
</div>
