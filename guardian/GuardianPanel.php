<?php

namespace Friendica\Addon\guardian;

use Friendica\DI;
use Friendica\Model\User;
use Friendica\Core\Renderer;
use Friendica\Content\Pager;

class GuardianPanel
{
    /**
     * Konstruktor ohne Argumente für maximale Kompatibilität
     */
    public function __construct()
    {
    }

    public function getAuditContent(): string
    {
        // Parameter aus der URL sicher abgreifen
        $sort_by   = $_GET['sort'] ?? 'register_date';
        $order     = $_GET['order'] ?? 'desc';
        $search    = isset($_GET['search']) ? trim($_GET['search']) : '';
        $view_mode = $_GET['view'] ?? '48h';

        // User-Liste abrufen (Limit auf 2000 für Performance)
        $users = User::getList(0, 2000, 'all');
        if (!is_array($users)) {
            $users = [];
        }

        // Blockliste aus der System-Konfiguration laden
        $disallowed_raw = DI::config()->get('system', 'disallowed_email');
        $disallowed_array = preg_split('/[\s,]+/', (string)$disallowed_raw, -1, PREG_SPLIT_NO_EMPTY);

        $filteredUsers = [];
        $now = time();
        $fortyEightHoursAgo = $now - (48 * 3600);

        foreach ($users as $u) {
            $u['display_name'] = !empty($u['name']) ? $u['name'] : $u['nickname'];
            $u['spam_score'] = 0;
            $u['spam_reasons'] = [];
            $email_lc = strtolower($u['email']);
            $domain = explode('@', $email_lc)[1] ?? '';
            $reg_time = strtotime($u['register_date']);

            // --- Scoring-Regeln ---
            foreach ($disallowed_array as $pattern) {
                if ($email_lc === strtolower(trim($pattern)) || $domain === strtolower(trim($pattern))) {
                    $u['spam_score'] += 100;
                    $u['spam_reasons'][] = "Blockliste: " . $pattern;
                    break;
                }
            }
            if (preg_match('/\.(top|xyz|bid|buzz|monster|pw|tk|gq|cf|ga|ml|work|date|faith|win|loan|stream|racing|accountant|review)$/i', $u['email'], $m)) {
                $u['spam_score'] += 60;
                $u['spam_reasons'][] = "TLD: ." . $m[1];
            }
            if (preg_match('/[0-9]{4,}/', $u['nickname'], $m)) {
                $u['spam_score'] += 30;
                $u['spam_reasons'][] = "Zahlen: " . $m[0];
            }
            if ($u['spam_score'] > 0 && $u['spam_score'] < 100) {
                if (preg_match('/(gmail|googlemail|gmx|web|outlook|hotmail|live|msn|yahoo|icloud|me|mac|proton|protonmail|freenet|mail|yandex|aol)\./i', $u['email'])) {
                    $u['spam_score'] += 10;
                    $u['spam_reasons'][] = "Korrelation: Freemailer";
                }
            }

            // --- Anzeige-Filter ---
            $show = false;
            if (!empty($search)) {
                if (strpos(strtolower($u['display_name']), strtolower($search)) !== false ||
                    strpos(strtolower($u['nickname']), strtolower($search)) !== false ||
                    strpos(strtolower($u['email']), strtolower($search)) !== false) {
                    $show = true;
                }
            } else {
                switch ($view_mode) {
                    case 'spam': if ($u['spam_score'] > 0) $show = true; break;
                    case 'pending': if ($u['pending']) $show = true; break;
                    case 'all': $show = true; break;
                    case '48h':
                    default: if ($reg_time >= $fortyEightHoursAgo) $show = true; break;
                }
            }

            if ($show) {
                $u['status_text'] = $u['pending'] ? 'Wartend' : ($u['blocked'] ? 'Gesperrt' : 'Aktiv');
                $u['status_class'] = $u['pending'] ? 'warning' : ($u['blocked'] ? 'danger' : 'success');
                $filteredUsers[] = $u;
            }
        }

        // --- Sortierung ---
        usort($filteredUsers, function($a, $b) use ($sort_by, $order, $view_mode) {
            if ($view_mode === '48h' || $view_mode === 'pending') {
                return strtotime($b['register_date']) <=> strtotime($a['register_date']);
            }
            return ($order === 'asc') ? ($a[$sort_by] <=> $b[$sort_by]) : ($b[$sort_by] <=> $a[$sort_by]);
        });

        // Pager Initialisierung
        $pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 50);

        return Renderer::replaceMacros(Renderer::getMarkupTemplate('guardian.tpl', 'addon/guardian'), [
            '$title'      => 'Guardian Spam Audit',
            '$count'      => count($filteredUsers),
            '$users'      => array_slice($filteredUsers, $pager->getStart(), 50),
            '$search_val' => $search,
            '$view_mode'  => $view_mode,
            '$sort_url'   => DI::baseUrl() . '/guardian',
            '$next_order' => ($order === 'asc' ? 'desc' : 'asc'),
            '$pager'      => $pager->renderFull(count($filteredUsers)),
            '$hilfe'      => Renderer::replaceMacros(Renderer::getMarkupTemplate('hilfe.tpl', 'addon/guardian'), []),
        ]);
    }
}
