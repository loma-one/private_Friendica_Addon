<div id="friendica-radio-player" class="panel panel-default">
    <div class="panel-body">
        <div class="row radio-player-row">

            <div class="col-xs-12 col-sm-4 radio-col">
                <div class="input-group">
                    <span class="input-group-addon"><i class="ri-radio-2-line" aria-hidden="true"></i></span>
                    <select id="radio-stream-select" class="form-control">
                        {{foreach $streams as $stream}}
                            <option value="{{$stream.url}}">{{$stream.name}}</option>
                        {{/foreach}}
                    </select>
                </div>
            </div>

            <div class="col-xs-6 col-sm-4 text-center radio-col">
                <button id="radio-play-btn" class="btn btn-success btn-block" type="button"
                        data-label-play="{{$lblPlay}}"
                        data-label-stop="{{$txtStop}}">
                    <i id="radio-play-icon" class="ri-play-fill" aria-hidden="true"></i>
                    <span id="radio-play-text"> {{$lblPlay}}</span>
                </button>
            </div>

            <div class="col-xs-6 col-sm-4 radio-col">
                <div class="radio-volume-container">
                    <i class="ri-volume-up-line radio-volume-icon" aria-hidden="true"></i>
                    <input type="range" id="radio-volume" min="0" max="1" step="0.05" value="0.8">
                </div>
            </div>

        </div>

        <div id="radio-status-msg" class="text-danger small" style="display: none;"
             data-msg-select="{{$txtSelectError}}"
             data-msg-loading="{{$txtLoading}}"
             data-msg-new="{{$txtNewSender}}"
             data-msg-play-error="{{$txtPlayError}}"
             data-msg-load-error="{{$txtLoadError}}"
             data-msg-conn-error="{{$txtConnError}}"></div>
    </div>
</div>
