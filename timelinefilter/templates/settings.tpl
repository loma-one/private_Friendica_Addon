<p>{{$info}}</p>

{{include file="field_checkbox.tpl" field=$enabled}}

<div class="form-group">
    <label class="control-label">{{$words_label}}</label>
    <div class="help-block">{{$words_help}}</div>

    <div id="tf-rules-container">
        {{foreach $rules as $index => $rule}}
        <div class="row tf-rule-row" style="margin-bottom: 10px; display: flex; align-items: center;">
            <div class="col-xs-5">
                <input type="text" name="tf-keywords[]" class="form-control" value="{{$rule.keyword}}" placeholder="e. g. facebook">
            </div>
            <div class="col-xs-3">
                <select name="tf-types[]" class="form-control">
                    <option value="hashtag" {{if $rule.type == 'hashtag'}}selected{{/if}}>Hashtag</option>
                    <option value="word" {{if $rule.type == 'word'}}selected{{/if}}>Word</option>
                </select>
            </div>
            <div class="col-xs-3">
                <select name="tf-durations[]" class="form-control">
                    <option value="always" {{if $rule.duration == 'always'}}selected{{/if}}>Allways</option>
                    <option value="1w" {{if $rule.duration == '1w'}}selected{{/if}}>1 Week</option>
                    <option value="1m" {{if $rule.duration == '1m'}}selected{{/if}}>1 Month</option>
                </select>
                <input type="hidden" name="tf-expires[]" value="{{$rule.expires}}">
            </div>
            <div class="col-xs-1" style="display: flex; justify-content: center;">
                <button type="button" class="btn btn-danger tf-remove-row" style="width: 25px; height: 25px; border-radius: 50%; padding: 0; line-height: 25px; text-align: center; font-weight: bold; border: none; background-color: #d9534f; color: #fff;">✕</button>
            </div>
        </div>
        {{/foreach}}
    </div>

    <div class="row" style="margin-top: 15px;">
        <div class="col-xs-12">
            <button type="button" id="tf-add-row" class="btn btn-default btn-sm" style="border-radius: 5px; padding: 6px 15px;">+ Add a rule</button>
        </div>
    </div>
</div>

<div id="tf-row-template" class="hidden">
    <div class="row tf-rule-row" style="margin-bottom: 10px; display: flex; align-items: center;">
        <div class="col-xs-5">
            <input type="text" name="tf-keywords[]" class="form-control" placeholder="Enter a term...">
        </div>
        <div class="col-xs-3">
            <select name="tf-types[]" class="form-control">
                <option value="hashtag">Hashtag</option>
                <option value="word">Word</option>
            </select>
        </div>
        <div class="col-xs-3">
            <select name="tf-durations[]" class="form-control">
                <option value="always">Allways</option>
                <option value="1w">1 Week</option>
                <option value="1m">1 Month</option>
            </select>
            <input type="hidden" name="tf-expires[]" value="0">
        </div>
        <div class="col-xs-1" style="display: flex; justify-content: center;">
            <button type="button" class="btn btn-danger tf-remove-row" style="width: 25px; height: 25px; border-radius: 50%; padding: 0; line-height: 25px; text-align: center; font-weight: bold; border: none; background-color: #d9534f; color: #fff;">✕</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const container = document.getElementById('tf-rules-container');
    const addButton = document.getElementById('tf-add-row');
    const template = document.getElementById('tf-row-template').firstElementChild;

    addButton.addEventListener('click', function() {
        const newRow = template.cloneNode(true);
        container.appendChild(newRow);
    });

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('tf-remove-row') || e.target.closest('.tf-remove-row')) {
            const row = e.target.closest('.tf-rule-row');
            if (row) row.remove();
        }
    });
});
</script>
