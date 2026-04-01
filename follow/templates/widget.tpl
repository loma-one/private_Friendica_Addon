<style>
    #follow-onboarding-widget {
        background: transparent !important;
        padding: 5px 0 !important;
        margin-bottom: 20px;
    }

    .fl-header h4 {
        margin: 0 0 10px 0 !important;
        font-size: 15px;
        font-weight: bold;
        color: inherit !important;
    }

    .fl-icon-stack {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        padding-left: 0;
        margin-left: 3px;
    }

    .fl-stack-item {
        margin-right: -10px;
        margin-bottom: 8px;
        position: relative;
        transition: transform 0.2s ease-in-out;
    }

    .fl-stack-item img {
        width: 37px !important;
        height: 37px !important;
        border-radius: 50% !important;
        border: 1px solid var(--body-bg, #fff) !important;
        box-shadow: 1px 1px 3px rgba(0,0,0,0.15);
        object-fit: cover;
        display: block;
    }

    .fl-stack-item:hover {
        z-index: 100 !important;
        transform: scale(1.2) translateY(-2px);
    }
</style>

<div class="widget" id="follow-onboarding-widget">
    <div class="fl-header">
        <h4>{{$title}}</h4>
    </div>

    <div class="fl-icon-stack">
        {{foreach $items as $item}}
            <div class="fl-stack-item" style="z-index: {{20 - $item@index}};">
                <a href="{{$item.url}}" title="{{$item.name}}" rel="noopener noreferrer">
                    <img src="{{$item.icon}}"
                         alt="{{$item.name}}"
                         referrerpolicy="no-referrer"
                         onerror="this.src='{{$fallback}}';">
                </a>
            </div>
        {{/foreach}}
    </div>
</div>
