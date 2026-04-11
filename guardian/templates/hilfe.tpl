<div class="panel-body" style="margin-top: 30px; border-top: 2px solid #eee; background-color: var(--nav-bg); padding: 20px;">
    <h1><i class="fa fa-shield"></i> Guardian Schutz-System</h1>

    <div class="row">
    <div class="col-md-6">
        <h5><i class="fa fa-bar-chart"></i> Analyse-Logik (Scoring)</h5>
        <ul class="list-group" style="margin-bottom: 0;">
            <li class="list-group-item">
                <strong><span class="badge alert-danger">+100</span> System-Blockliste:</strong>
                Exakte Übereinstimmung mit der globalen E-Mail-Sperrliste.
            </li>
            <li class="list-group-item">
                <strong><span class="badge alert-danger">+60</span> Spam-TLD:</strong>
                Verwendung von riskanten Endungen wie <code>.top, .xyz, .bid, .monster, .pw</code>.
            </li>
            <li class="list-group-item">
                <strong><span class="badge alert-warning">+20 bis +80</span> Namens-Dubletten:</strong>
                Identifiziert koordinierte Wellen. Punkte steigen mit der Anzahl identischer Anzeigenamen.
            </li>
            <li class="list-group-item">
                <strong><span class="badge alert-warning">+30</span> Zahlenfolge:</strong>
                4 oder mehr aufeinanderfolgende Ziffern im Nickname (typisch für Bot-Generatoren).
            </li>
            <li class="list-group-item">
                <strong><span class="badge alert-info">+10</span> Korrelation:</strong>
                Zusatzpunkt bei Freemailern, falls bereits andere Merkmale auf Spam hindeuten.
            </li>
        </ul>
    </div>

    <div class="col-md-6">
        <h5><i class="fa fa-filter"></i> Ansichts-Modi</h5>
        <p>Das Panel durchsucht die letzten 2000 Registrierungen:</p>
        <ul>
            <li><strong>Letzte 48 Stunden:</strong> Fokus auf aktuelle Aktivitäten (standardmäßig nach Datum sortiert).</li>
            <li><strong>Spam-Verdacht:</strong> Zeigt nur Accounts mit Score > 0. Ideal für die schnelle Moderation.</li>
            <li><strong>Nur ausstehende:</strong> Listet Accounts, die noch manuell bestätigt werden müssen.</li>
            <li><strong>Alle Accounts:</strong> Vollständige Durchsicht der geladenen Daten.</li>
        </ul>
        <p><small class="text-muted">Die Suche ignoriert den gewählten Modus und durchsucht immer alle 2000 Datensätze.</small></p>
    </div>
</div>

<hr>

<div class="alert alert-info">
    <i class="fa fa-lightbulb-o"></i> <strong>Workflow-Tipp:</strong> Nutze die Checkboxen links, um Accounts zu markieren, die du bereits geprüft hast. Diese "Gelesen"-Markierung wird sicher in deinem Browser gespeichert (localStorage), damit du bei der nächsten Prüfung sofort siehst, wo du aufgehört hast.
</div>

<div class="panel-footer" style="background: none; border: none; padding: 0; margin-top: 10px;">
    <small class="text-muted"><em>Hinweis: Ein hoher Score ist ein starkes Indiz, aber kein automatischer Löschgrund. Bitte prüfe bei Verdacht das Profil oder die IP-Historie.</em></small>
</div>
