<p>{{$info}}</p>

{{include file="field_checkbox.tpl" field=$enabled}}

<!-- Wichtig für die POST-Validierung in PHP -->
<input type="hidden" name="timelinefilter-submit" value="1">

<div class="form-group margin-top-sub">
    <label class="control-label">{{$words_label}}</label>
    <div class="help-block" style="margin-bottom: 15px;">{{$words_help}}</div>

    <div id="tf-rules-container">
        {{foreach $rules as $rule}}
        <div class="row tf-rule-row" style="margin-bottom: 12px; display: flex; align-items: center;">
            <div class="col-xs-5">
                <input type="text" name="tf-keywords[]" class="form-control" value="{{$rule.keyword}}" placeholder="z. B. facebook oder @user@domain.ltd">
            </div>
            <div class="col-xs-3">
                <select name="tf-types[]" class="form-control">
                    <option value="hashtag" {{if $rule.type == 'hashtag'}}selected{{/if}}>Hashtag</option>
                    <option value="word" {{if $rule.type == 'word'}}selected{{/if}}>Word</option>
                    <option value="account" {{if $rule.type == 'account'}}selected{{/if}}>Account</option>
                </select>
            </div>
            <div class="col-xs-3">
                <select name="tf-durations[]" class="form-control">
                    <option value="always" {{if $rule.duration == 'always'}}selected{{/if}}>{{$opt_always}}</option>
                    <option value="1d" {{if $rule.duration == '1d'}}selected{{/if}}>{{$opt_1d}}</option>
                    <option value="1w" {{if $rule.duration == '1w'}}selected{{/if}}>{{$opt_1w}}</option>
                    <option value="1m" {{if $rule.duration == '1m'}}selected{{/if}}>{{$opt_1m}}</option>
                </select>
                <input type="hidden" name="tf-expires[]" value="{{$rule.expires}}">

                {{if $rule.days_left !== null}}
                    <small class="help-block" style="margin-top: 4px; margin-bottom: 0;">
                        {{$rule.days_left_text}}
                    </small>
                {{/if}}
            </div>
            <div class="col-xs-1 text-center">
                <button type="button" class="btn btn-danger tf-remove-row" style="width: 28px; height: 28px; border-radius: 50%; padding: 0; line-height: 28px; text-align: center; font-weight: bold; border: none; font-size: 14px;" aria-label="Delete rule">✕</button>
            </div>
        </div>
        {{/foreach}}
    </div>

    <div class="row" style="margin-top: 20px;">
        <div class="col-xs-12">
            <button type="button" id="tf-add-row" class="btn btn-default btn-sm" style="padding: 6px 16px;">
                <i class="fa fa-plus"></i> Add a rule
            </button>
        </div>
    </div>
</div>

<!-- Template für neue Zeilen -->
<div id="tf-row-template" class="hidden">
    <div class="row tf-rule-row" style="margin-bottom: 12px; display: flex; align-items: center;">
        <div class="col-xs-5">
            <input type="text" name="tf-keywords[]" class="form-control" placeholder="Enter a term or @user@domain...">
        </div>
        <div class="col-xs-3">
            <select name="tf-types[]" class="form-control">
                <option value="hashtag">Hashtag</option>
                <option value="word">Word</option>
                <option value="account">Account</option>
            </select>
        </div>
        <div class="col-xs-3">
            <select name="tf-durations[]" class="form-control">
                <option value="always">{{$opt_always}}</option>
                <option value="1d">{{$opt_1d}}</option>
                <option value="1w">{{$opt_1w}}</option>
                <option value="1m">{{$opt_1m}}</option>
            </select>
            <input type="hidden" name="tf-expires[]" value="0">
        </div>
        <div class="col-xs-1 text-center">
            <button type="button" class="btn btn-danger tf-remove-row" style="width: 28px; height: 28px; border-radius: 50%; padding: 0; line-height: 28px; text-align: center; font-weight: bold; border: none; font-size: 14px;" aria-label="Delete rule">✕</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById('tf-rules-container');
    const addButton = document.getElementById('tf-add-row');
    const template = document.getElementById('tf-row-template').firstElementChild;

    addButton?.addEventListener('click', () => {
        container.appendChild(template.cloneNode(true));
    });

    container?.addEventListener('click', (e) => {
        const btn = e.target.closest('.tf-remove-row');
        if (btn) {
            btn.closest('.tf-rule-row')?.remove();
        }
    });
});
</script>
