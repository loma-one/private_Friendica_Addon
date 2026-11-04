<div class="addon-admin-page">
	<h3>MediaCompress</h3>
	{{$status nofilter}}
	<p>Verkleinert Video- und MP3-Anlagen serverseitig (cron-gesteuert, max. eine Datei pro Lauf, mit nice/Timeout/Größenlimit). Das Original wird nur ersetzt, wenn das Ergebnis kleiner und valide ist; bestehende Beitrags-Links bleiben intakt.</p>

	<div class="well">
		<p><strong>Cron (Friendica):</strong> {{$cron nofilter}}</p>
		<p><strong>Letzter Addon-Lauf:</strong> {{$lastrun}}</p>
		<p><strong>Status:</strong> {{$ffmpeg nofilter}}</p>
		<p><strong>Statistik:</strong> {{$converted}} Dateien konvertiert, ca. <strong>{{$savedmb}} MB</strong> gespart, {{$done}} erledigt, {{$failed}} fehlerhaft übersprungen, {{$pending}} Originale zurückgestellt.</p>
	</div>

	{{include file="field_checkbox.tpl" field=$enabled}}
	<div class="row">
		<div class="col-md-4">{{include file="field_input.tpl" field=$interval}}</div>
		<div class="col-md-4">{{include file="field_input.tpl" field=$maxsize}}</div>
		<div class="col-md-4">{{include file="field_input.tpl" field=$ffmpeg_path}}</div>
	</div>
	<div class="row">
		<div class="col-md-3">{{include file="field_input.tpl" field=$video_height}}</div>
		<div class="col-md-3">{{include file="field_input.tpl" field=$crf}}</div>
		<div class="col-md-3">{{include file="field_input.tpl" field=$audio_bitrate}}</div>
		<div class="col-md-3">{{include file="field_input.tpl" field=$timeout}}</div>
	</div>
	{{include file="field_input.tpl" field=$retention}}
	{{include file="field_input.tpl" field=$fileperm}}

	<div class="submit">
		<input type="submit" name="mediacompress-submit" class="btn btn-primary" value="{{$submit}}">
		<input type="submit" name="mediacompress-process" class="btn btn-default" value="{{$process}}">
		<input type="submit" name="mediacompress-retry" class="btn btn-default" value="{{$retry}}">
	</div>
</div>
