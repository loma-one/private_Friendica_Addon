<p>{{$description}}</p>
{{include file="field_checkbox.tpl" field=$enabled}}
<label for="date_format">{{$dateFormatLabel}}</label>
<select name="date_format">
    <option value="d.m.Y">DD.MM.YYYY</option>
    <option value="Y-m-d">YYYY-MM-DD</option>
    <option value="m/d/Y">MM/DD/YYYY</option>
</select>
