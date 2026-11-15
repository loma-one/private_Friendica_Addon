<span class="quicktag-wrapper">
	<button type="button" id="quicktag-toggle-btn" class="btn btn-default" title="{{$title}}" aria-haspopup="true" aria-expanded="false">
		<i class="ri-hashtag" aria-hidden="true"></i>
	</button>

	<div id="quicktag-popover" class="dropdown-menu quicktag-popover" hidden>
		<div class="quicktag-header">{{$title}}</div>
		<div class="quicktag-chips">
			{{foreach $tags as $tag}}
				<button type="button" class="btn btn-default btn-xs quicktag-chip" data-tag="{{$tag}}">{{$tag}}</button>
			{{/foreach}}
		</div>
	</div>
</span>
