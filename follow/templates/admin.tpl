<div class="settings-block">
    <h3>Follow Suggestions Admin</h3>
    <div class="section-description">
        <p>{{$info}}</p>
        <p><strong>Last Update:</strong> {{$last_update}}</p>
        <p><strong>Next Update:</strong> {{$next_update}}</p>
    </div>

    <div class="settings-submit-wrapper">
        <form action="admin/addons/follow" method="post">
            <input type="submit" name="follow_reset_cache" class="settings-submit btn-danger" value="Clear cache now" style="background-color: #d9534f; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;" />
        </form>
    </div>
</div>
