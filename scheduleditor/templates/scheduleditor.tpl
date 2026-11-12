<div class="generic-page-wrapper scheduleditor-page">
	<div class="section-title-wrapper clearfix">
		<h2 class="heading">
			{{$title}}
			<a href="{{$baseurl}}/scheduleditor/new" class="btn btn-primary btn-sm pull-right" title="{{$new_post}}" aria-label="{{$new_post}}">
				<i class="ri ri-add-circle-line" aria-hidden="true"></i> <span class="hidden-xs-inline">{{$new_post}}</span>
			</a>
		</h2>
	</div>

{{if $show_form}}
		<div class="comment-edit-form-wrapper" id="profile-jot-wrapper">
			<form class="scheduleditor-compose-form" data-item-id="{{$post_id}}" id="comment-edit-form-{{$post_id}}"
			      action="{{$baseurl}}/scheduleditor" method="post">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<input type="hidden" name="action" value="{{$form_action}}">
				<input type="hidden" name="id" value="{{$post_id}}">
				<input type="hidden" name="post_id_random" value="{{$rand_num}}">

				{{if $private_notice}}
					<div class="alert alert-info" role="alert">
						<i class="ri ri-lock-line" aria-hidden="true"></i> {{$private_notice}}
					</div>
				{{/if}}

				<div id="jot-title-wrap">
					<input type="text" name="scheduleditor_title" id="jot-title"
					       class="jothidden jotforms form-control"
					       placeholder="{{$field_title}}" title="{{$field_title}}"
					       value="{{$value_title}}" tabindex="1" dir="auto">
				</div>

				<div id="jot-summary-wrap">
					<input type="text" name="scheduleditor_summary" id="jot-summary"
					       class="jothidden jotforms form-control"
					       placeholder="{{$field_summary}}" title="{{$field_summary}}"
					       value="{{$value_summary}}" tabindex="2" dir="auto">
				</div>

				<div class="form-group" style="margin-top:10px;">
					<div class="input-group">
						<span class="input-group-addon"><i class="ri ri-calendar-check-line" aria-hidden="true"></i></span>
						<input type="datetime-local" class="form-control" id="scheduleditor-scheduled-at"
						       name="scheduleditor_scheduled_at" value="{{$value_scheduled_at}}"
						       title="{{$field_scheduled_at}}" tabindex="3">
					</div>
				</div>

				<div class="comment-edit-bb-{{$post_id}} btn-toolbar clearfix" role="toolbar">
					<div class="btn-group">
						<button type="button" class="btn btn-default bb-img" aria-label="{{$l10n_img}}" title="{{$l10n_img}}"
						        data-role="insert-formatting" data-bbcode="img" data-id="{{$post_id}}" tabindex="4">
							<i class="ri ri-image-line"></i>
						</button>
						<button type="button" class="btn btn-default bb-attach" aria-label="{{$l10n_attach}}" title="{{$l10n_attach}}"
						        ondragenter="return commentLinkDrop(event, {{$post_id}});"
						        ondragover="return commentLinkDrop(event, {{$post_id}});"
						        ondrop="commentLinkDropper(event);"
						        onclick="commentGetLink({{$post_id}}, '{{$l10n_prompt}}');" tabindex="5">
							<i class="ri ri-attachment-2"></i>
						</button>
						<button type="button" id="button_emojipicker" class="btn btn-default emojis"
						        aria-label="{{$l10n_emojis}}" title="{{$l10n_emojis}}" tabindex="6">
							<i class="ri ri-emotion-line"></i>
						</button>
					</div>
					<div class="pull-right">
						<div class="btn-group">
							<button type="button" class="btn btn-default bb-url" aria-label="{{$l10n_url}}" title="{{$l10n_url}}"
							        onclick="insertFormatting('url', {{$post_id}});" tabindex="7">
								<i class="ri ri-link"></i>
							</button>
							<button type="button" class="btn btn-default underline" aria-label="{{$l10n_uline}}" title="{{$l10n_uline}}"
							        onclick="insertFormatting('u', {{$post_id}});" tabindex="9">
								<i class="ri ri-underline"></i>
							</button>
							<button type="button" class="btn btn-default italic" aria-label="{{$l10n_italic}}" title="{{$l10n_italic}}"
							        onclick="insertFormatting('i', {{$post_id}});" tabindex="10">
								<i class="ri ri-italic"></i>
							</button>
							<button type="button" class="btn btn-default bold" aria-label="{{$l10n_bold}}" title="{{$l10n_bold}}"
							        onclick="insertFormatting('b', {{$post_id}});" tabindex="11">
								<i class="ri ri-bold"></i>
							</button>
						</div>
						<div class="btn-group">
							<button type="button" class="btn btn-default quote" aria-label="{{$l10n_quote}}" title="{{$l10n_quote}}"
							        onclick="insertFormatting('quote', {{$post_id}});" tabindex="12">
								<i class="ri ri-double-quotes-l"></i>
							</button>
							<button type="button" class="btn btn-default bb-url" aria-label="{{$l10n_cw}}" title="{{$l10n_cw}}"
							        onclick="insertFormatting('abstract', {{$post_id}});" tabindex="13">
								<i class="ri ri-eye-line"></i>
							</button>
							<button type="button" class="btn btn-default code" aria-label="{{$l10n_code}}" title="{{$l10n_code}}"
							        onclick="insertFormatting('code', {{$post_id}});" tabindex="14">
								<i class="ri ri-code-line"></i>
							</button>
						</div>
					</div>
				</div>

				<div id="dropzone-{{$post_id}}" class="dropzone">
					<p>
						<textarea id="comment-edit-text-{{$post_id}}"
						          class="comment-edit-text form-control text-autosize expandable-textarea"
						          name="scheduleditor_body" placeholder="{{$field_body}}"
						          rows="18" tabindex="3" dir="auto">{{$value_body}}</textarea>
					</p>
				</div>

				<div class="comment-edit-submit-wrapper clearfix">
					<div class="pull-right">
						<button type="button" class="btn btn-default comment-edit-preview"
						        onclick="scheduleditorPreviewToggle({{$post_id}}, '{{$l10n_preview}}', '{{$l10n_close_preview}}');"
						        id="comment-edit-preview-link-{{$post_id}}">
							<i class="ri ri-eye-line" aria-hidden="true"></i> <span id="preview-btn-text-{{$post_id}}">{{$l10n_preview}}</span>
						</button>
						<button type="submit" class="btn btn-primary" id="comment-edit-submit-{{$post_id}}" tabindex="9">
							<i class="ri ri-send-plane-line"></i> {{$save}}
						</button>
						<a href="{{$baseurl}}/scheduleditor" class="btn btn-default">{{$cancel}}</a>
					</div>
				</div>
				<div id="comment-edit-preview-{{$post_id}}" class="comment-edit-preview" style="display:none;"></div>
			</form>
		</div>
		<script>
			dzFactory.setupDropzone('#dropzone-{{$post_id}}', 'comment-edit-text-{{$post_id}}');

			function scheduleditorPreviewToggle(id, previewLabel, closeLabel) {
				var pane    = document.getElementById('comment-edit-preview-' + id);
				var btnText = document.getElementById('preview-btn-text-' + id);
				if (pane.style.display === 'block') {
					pane.style.display = 'none';
					btnText.textContent = previewLabel;
					return;
				}
				var form    = document.getElementById('comment-edit-form-' + id);
				var body    = form.querySelector('[name="scheduleditor_body"]').value;
				var title   = form.querySelector('[name="scheduleditor_title"]').value;
				var summary = form.querySelector('[name="scheduleditor_summary"]').value;
				jQuery.post('{{$baseurl}}/item', {preview: 1, wall: 1, body: body, title: title, summary: summary})
					.done(function(data) {
						pane.innerHTML = data.preview || '';
						pane.querySelectorAll('a').forEach(function(link) {
							link.onclick = function() { return false; };
						});
						pane.style.display = 'block';
						btnText.textContent = closeLabel;
					});
			}

			window.onDocumentReady('body', function() {
				var textareas = document.querySelectorAll('.expandable-textarea');

				textareas.forEach(function(textarea) {
					textarea.style.height = 'auto';
					textarea.style.height = (textarea.scrollHeight) + 'px';

					textarea.addEventListener('input', function() {
						this.style.height = 'auto';
						this.style.height = (this.scrollHeight) + 'px';
					});
				});
			});
		</script>
{{else}}
			{{if $entries}}
				<div class="table-responsive">
					<table class="table table-hover table-striped scheduleditor-list">
						<thead>
						<tr>
							<th scope="col">{{$scheduled_at}}</th>
							<th scope="col">{{$content}}</th>
							<th scope="col" class="text-right"></th>
						</tr>
						</thead>
						<tbody>
						{{foreach $entries as $entry}}
							<tr>
								<td class="scheduleditor-date" data-label="{{$scheduled_at}}">
									<i class="ri ri-calendar-line" aria-hidden="true"></i>
									{{$entry.scheduled_at}}
								</td>
								<td class="scheduleditor-content" data-label="{{$content}}">
									{{if $entry.title}}<strong>{{$entry.title}}</strong><br>{{/if}}
									{{$entry.preview}}
									{{if $entry.private}}
										&nbsp;<i class="ri ri-lock-line" title="{{$private}}" aria-hidden="true"></i>
									{{/if}}
								</td>
								<td class="text-right scheduleditor-actions">
									<a href="{{$baseurl}}/{{$entry.edit_link}}" class="btn btn-sm btn-primary"
									   title="{{$edit}}" aria-label="{{$edit}}">
										<i class="ri ri-pencil-line" aria-hidden="true"></i> <span class="hidden-xs-inline">{{$edit}}</span>
									</a>
									<form action="{{$baseurl}}/scheduleditor" method="post" style="display:inline;">
										<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
										<input type="hidden" name="action" value="delete">
										<input type="hidden" name="id" value="{{$entry.id}}">
										<button type="submit" class="btn btn-sm btn-danger"
										        title="{{$delete}}" aria-label="{{$delete}}"
										        onclick="return confirm('{{$delete}}?');">
											<i class="ri ri-delete-bin-line" aria-hidden="true"></i> <span class="hidden-xs-inline">{{$delete}}</span>
										</button>
									</form>
								</td>
							</tr>
						{{/foreach}}
						</tbody>
					</table>
				</div>
			{{else}}
				<p class="text-muted"><i class="ri ri-information-line" aria-hidden="true"></i> {{$empty}}</p>
			{{/if}}

			<hr>
			<a href="{{$baseurl}}/profile/{{$nickname}}/schedule" class="btn btn-link">
				<i class="ri ri-time-line" aria-hidden="true"></i> {{$core_schedule}}
			</a>
{{/if}}

	</div>
