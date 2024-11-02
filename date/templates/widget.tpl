<div id="curweather-network" class="widget">
    <div class="title tool clear">
        <h3 title="{{$currentDate}}">{{$title}}: {{$currentDate}}</h3>
    </div>
    <div class="pull-left">
        <ul class="curweather-details">
            <li><strong>{{$currentDate}}</strong></li>
        </ul>
    </div>
    <div class="clear"></div>
    <div class="curweather-footer pull-left">
        <p>{{DI::l10n()->t('Displayed date is based on server time.')}}</p>
    </div>
</div>
<div class="clear"></div>
