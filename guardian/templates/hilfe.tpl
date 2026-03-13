<div class="panel-body" style="margin-top: 30px; border-top: 2px solid #eee; background-color: #f9f9f9; padding: 20px;">
    <h4><i class="fa fa-shield"></i> Guardian Schutz-System</h4>

    <div class="row">
        <div class="col-md-6">
            <h5><i class="fa fa-bar-chart"></i> Analyse-Logik (Scoring)</h5>
            <ul class="list-group">
                <li class="list-group-item">
                    <strong><span class="badge alert-danger">+100</span> System-Blockliste:</strong>
                    Exakte Übereinstimmung mit <em>Admin -> Sicherheit -> E-Mail-Sperrliste</em>.
                </li>
                <li class="list-group-item">
                    <strong><span class="badge alert-danger">+60</span> Spam-TLD:</strong>
                    Endungen wie <code>.top, .xyz, .bid, .buzz, .monster, .pw, .tk</code>.
                </li>
                <li class="list-group-item">
                    <strong><span class="badge alert-warning">+30</span> Zahlenfolge:</strong>
                    4 oder mehr aufeinanderfolgende Ziffern im Nickname.
                </li>
            </ul>
        </div>

        <div class="col-md-6">
            <h5><i class="fa fa-bug"></i> Honeypot-Status (Prävention)</h5>
            <p>Die serverseitige Falle ist <strong>aktiv</strong>. Damit Bots blockiert werden, muss folgendes Feld in der <code>register.tpl</code> deines Themes verbaut sein:</p>
            <pre style="font-size: 10px; background: #eee; padding: 5px; border: 1px solid #ccc;">
&lt;div style="display:none;"&gt;
  &lt;input type="text" name="special_mail_field"&gt;
&lt;/div&gt;</pre>
            <p><small class="text-muted">Bots, die in diese Falle tappen, werden sofort mit "403 Forbidden" abgewiesen und erscheinen gar nicht erst in dieser Liste.</small></p>
        </div>
    </div>

    <hr>

    <div class="alert alert-info">
        <i class="fa fa-lightbulb-o"></i> <strong>Workflow-Tipp:</strong> Nutze das Auge-Symbol <i class="fa fa-eye"></i> in der Tabelle, um Datensätze als "geprüft" zu markieren. Dies wird lokal in deinem Browser gespeichert und hilft dir, den Überblick bei vielen Neuanmeldungen zu behalten.
    </div>
</div>
