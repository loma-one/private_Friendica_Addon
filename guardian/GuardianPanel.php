<?php

namespace Friendica\Addon\guardian;

use Friendica\DI;
use Friendica\Model\User;
use Friendica\Core\Renderer;
use Friendica\Content\Pager;

class GuardianPanel
{
    public function getAuditContent(): string
    {
        $sort_by = $_GET['sort'] ?? 'register_date';
        $order   = $_GET['order'] ?? 'desc';
        $search  = isset($_GET['search']) ? trim($_GET['search']) : '';
        $only_pending = isset($_GET['pending']) ? (int)$_GET['pending'] : 0;

        $users = User::getList(0, 1000, 'all');
        if (!is_array($users)) {
            $users = [];
        }

        $disallowed_raw = DI::config()->get('system', 'disallowed_email');
        $disallowed_array = preg_split('/[\s,]+/', (string)$disallowed_raw, -1, PREG_SPLIT_NO_EMPTY);

        $filteredUsers = [];
        foreach ($users as $u) {
            if ($only_pending && !$u['pending']) {
                continue;
            }

            $u['display_name'] = !empty($u['name']) ? $u['name'] : $u['nickname'];

            // --- Scoring System ---
            $u['spam_score'] = 0;
            $u['spam_reasons'] = [];
            $email_lc = strtolower($u['email']);
            $domain = explode('@', $email_lc)[1] ?? '';

            // 1. Check: Manuelle Blockliste (100 Punkte)
            foreach ($disallowed_array as $pattern) {
                $p_lc = strtolower(trim($pattern));
                if ($email_lc === $p_lc || $domain === $p_lc) {
                    $u['spam_score'] += 100;
                    $u['spam_reasons'][] = "Blockliste: " . $p_lc;
                    break;
                }
            }

            // 2. Check: Verdächtige TLDs (60 Punkte)
            $tld_pattern = '/\.(top|xyz|bid|buzz|monster|pw|tk|gq|cf|ga|ml|work|date|faith|win|loan|stream|racing|accountant|review)$/i';
            if (preg_match($tld_pattern, $u['email'], $matches)) {
                $u['spam_score'] += 60;
                $u['spam_reasons'][] = "TLD: ." . strtolower($matches[1]);
            }

            // 3. Check: Lange Zahlenketten im Nickname (30 Punkte)
            if (preg_match('/[0-9]{4,}/', $u['nickname'], $matches)) {
                $u['spam_score'] += 30;
                $u['spam_reasons'][] = "Zahlen: " . $matches[0];
            }

            // 4. KORRELATION: Bonus für Freemailer NUR bei bestehendem Verdacht
            if ($u['spam_score'] > 0 && $u['spam_score'] < 100) {
                $freemailer_pattern = '/(gmail|googlemail|gmx|web|outlook|hotmail|live|msn|yahoo|icloud|me|mac|proton|protonmail|freenet|mail|yandex|aol)\./i';
                if (preg_match($freemailer_pattern, $u['email'], $mail_matches)) {
                    $u['spam_score'] += 10;
                    $u['spam_reasons'][] = "Kombination: " . strtolower($mail_matches[1]) . " + Verdacht";
                }
            }

            // --- Suche-Logik ---
            $is_search_match = false;
            if (!empty($search)) {
                $search_lc = strtolower($search);
                if (strpos(strtolower($u['display_name']), $search_lc) !== false ||
                    strpos(strtolower($u['nickname']), $search_lc) !== false ||
                    strpos(strtolower($u['email']), $search_lc) !== false) {
                    $is_search_match = true;
                }
            }

            // --- Anzeige-Entscheidung ---
            $show = false;
            if (!empty($search)) {
                if ($is_search_match) $show = true;
            } else {
                if ($u['spam_score'] > 0) $show = true;
            }

            if ($show) {
                $u['status_text'] = 'Aktiv';
                $u['status_class'] = 'success';
                if ($u['pending']) {
                    $u['status_text'] = 'Wartend';
                    $u['status_class'] = 'warning';
                } elseif ($u['account_removed']) {
                    $u['status_text'] = 'Gelöscht';
                    $u['status_class'] = 'danger';
                } elseif ($u['blocked']) {
                    $u['status_text'] = 'Gesperrt';
                    $u['status_class'] = 'danger';
                } elseif ($u['account_expired']) {
                    $u['status_text'] = 'Inaktiv';
                    $u['status_class'] = 'default';
                }
                $filteredUsers[] = $u;
            }
        }

        usort($filteredUsers, function($a, $b) use ($sort_by, $order) {
            $valA = $a[$sort_by] ?? '';
            $valB = $b[$sort_by] ?? '';
            return ($order === 'asc') ? ($valA <=> $valB) : ($valB <=> $valA);
        });

        $pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 50);
        return Renderer::replaceMacros(Renderer::getMarkupTemplate('guardian.tpl', 'addon/guardian'), [
            '$title' => 'Guardian Spam Audit',
            '$count' => count($filteredUsers),
            '$users' => array_slice($filteredUsers, $pager->getStart(), 50),
            '$search_val' => $search,
            '$only_pending' => $only_pending,
            '$sort_url' => DI::baseUrl() . '/guardian',
            '$current_sort' => $sort_by,
            '$next_order' => ($order === 'asc' ? 'desc' : 'asc'),
            '$pager' => $pager->renderFull(count($filteredUsers)),
            '$hilfe' => Renderer::replaceMacros(Renderer::getMarkupTemplate('hilfe.tpl', 'addon/guardian'), []),
        ]);
    }
}
