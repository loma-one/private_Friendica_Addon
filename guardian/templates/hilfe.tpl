<div class="panel panel-info">
    <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-info-circle"></i> Guardian Audit Hilfe & Scoring-Logik</h3>
    </div>
    <div class="panel-body">
        <p>Das Guardian-System bewertet Neuregistrierungen anhand von Spam-Mustern. Ein hoher <strong>Score</strong> führt zur farblichen Hervorhebung der Zeile.</p>

        <div class="row">
            <div class="col-md-6">
                <h4>Scoring-Regeln</h4>
                <ul class="list-group">
                    <li class="list-group-item">
                        <span class="badge">100</span>
                        <strong>Manuelle Blockliste:</strong> E-Mail oder Domain steht in den System-Einstellungen unter <em>disallowed_email</em>.
                    </li>
                    <li class="list-group-item">
                        <span class="badge">60</span>
                        <strong>Verdächtige TLDs:</strong> Domains mit Endungen wie <code>.top, .xyz, .bid, .monster, .pw, .work, .tk</code> etc.
                    </li>
                    <li class="list-group-item">
                        <span class="badge">30</span>
                        <strong>Nickname-Muster:</strong> Der Benutzername enthält eine Kette von 4 oder mehr aufeinanderfolgenden Zahlen.
                    </li>
                    <li class="list-group-item">
                        <span class="badge">+10</span>
                        <strong>Korrelation (Freemailer):</strong> Zusätzliche Punkte, wenn bereits ein Verdacht besteht UND ein bekannter Provider (Gmail, GMX, Proton, iCloud, etc.) genutzt wird.
                    </li>
                </ul>
            </div>

            <div class="col-md-6">
                <h4>Bedienung</h4>
                <ul>
                    <li><strong>Status-Farben:</strong>
                        <span class="label label-danger">Rot (Score >= 100)</span>,
                        <span class="label label-warning">Gelb (Score >= 30)</span>.
                    </li>
                    <li><strong>Lokale Markierung:</strong> Über die Checkbox ganz links kannst du bearbeitete Zeilen ausgrauen. Diese Markierung wird nur in deinem Browser gespeichert (LocalStorage).</li>
                    <li><strong>Filter:</strong> Nutze "Nur ausstehende", um gezielt Benutzer zu finden, die noch auf ihre Freischaltung warten.</li>
                    <li><strong>Suche:</strong> Sucht übergreifend in Name, Nickname und E-Mail-Adresse.</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="panel-footer">
        <small>Hinweis: Ein hoher Score ist ein Indikator, kein Beweis. Bitte prüfe im Zweifel die Details des Benutzers.</small>
    </div>
</div>
