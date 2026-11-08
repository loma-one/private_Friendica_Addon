<div class="panel panel-default">
	<div class="panel-heading">
		<h3>{{$title}}</h3>
	</div>
	<div class="panel-body">

		<p>
			<a href="settings/addons" class="btn btn-default btn-sm">
				<i class="fa fa-arrow-circle-left" aria-hidden="true"></i>
				{{$back}}
			</a>
		</p>

		<p>{{$info}}</p>

		<form action="timelinefilter" method="post">
			{{include file="field_checkbox.tpl" field=$enabled}}

			<input type="hidden" name="timelinefilter-submit" value="1">

			<div class="form-group">
				<label class="control-label">{{$words_label}}</label>
				<div class="help-block">{{$words_help}}</div>

				<div id="tf-rules-container">
					{{foreach $rules as $rule}}
					<div class="form-group tf-rule-row" style="margin-bottom: 12px; display: flex; align-items: center;">
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
							<button type="button" class="btn btn-danger tf-remove-row" style="width: 28px; height: 28px; border-radius: 50%; padding: 0; line-height: 28px; text-align: center; font-weight: bold; border: none; font-size: 14px;" aria-label="Delete rule">&#10005;</button>
						</div>
					</div>
					{{/foreach}}
				</div>

				<div style="margin-top: 20px;">
					<button type="button" id="tf-add-row" class="btn btn-default btn-sm" style="padding: 6px 16px;">
						<i class="fa fa-plus"></i> {{$add_rule}}
					</button>
				</div>
			</div>

			<div class="form-group">
				<button type="submit" class="btn btn-primary" value="1">{{$submit}}</button>
			</div>
		</form>

		<div id="tf-row-template" class="hidden">
			<div class="form-group tf-rule-row" style="margin-bottom: 12px; display: flex; align-items: center;">
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
					<button type="button" class="btn btn-danger tf-remove-row" style="width: 28px; height: 28px; border-radius: 50%; padding: 0; line-height: 28px; text-align: center; font-weight: bold; border: none; font-size: 14px;" aria-label="Delete rule">&#10005;</button>
				</div>
			</div>
		</div>

	</div>
</div>

<script>
if (!window.__timelinefilterSettingsBound) {
	window.__timelinefilterSettingsBound = true;

	document.addEventListener("click", function (e) {
		var target = e.target;
		if (!target || target.nodeType !== 1) {
			return;
		}

		// Vorfahren eines Elements nach einer Klasse durchsuchen
		var findAncestor = function (el, className) {
			while (el && el !== document) {
				if (el.classList && el.classList.contains(className)) {
					return el;
				}
				el = el.parentNode;
			}
			return null;
		};

		// Klick auf "Add a rule": Button selbst oder Inhalt getroffen
		var el = target;
		while (el && el !== document) {
			if (el.id === "tf-add-row") {
				var container = document.getElementById("tf-rules-container");
				var template = document.getElementById("tf-row-template");
				var templateRow = template ? template.firstElementChild : null;
				if (container && templateRow) {
					container.appendChild(templateRow.cloneNode(true));
				}
				return;
			}
			el = el.parentNode;
		}

		// Klick auf den Loeschen-Button einer Regel
		var removeBtn = findAncestor(target, "tf-remove-row");
		if (removeBtn) {
			var row = findAncestor(removeBtn, "tf-rule-row");
			if (row && row.parentNode) {
				row.parentNode.removeChild(row);
			}
		}
	}, true);
}
</script>
