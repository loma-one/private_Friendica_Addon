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
        $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'register_date';
        $order   = isset($_GET['order']) ? $_GET['order'] : 'desc';
        $next_order = ($order === 'asc') ? 'desc' : 'asc';

        // Wir laden alle ausstehenden User, um den Score für alle zu berechnen
        // (Da der Score nicht in der DB steht, müssen wir lokal filtern)
        $users = User::getList(0, 1000, 'pending');

        $disallowed_raw = DI::config()->get('system', 'disallowed_email');
        $disallowed_array = [];
        if (!empty($disallowed_raw)) {
            $disallowed_array = preg_split('/[\s,]+/', $disallowed_raw, -1, PREG_SPLIT_NO_EMPTY);
            $disallowed_array = array_map('strtolower', array_map('trim', $disallowed_array));
        }

        $filteredUsers = [];
        if (is_array($users)) {
            foreach ($users as $u) {
                $u['spam_score'] = 0;
                $u['spam_reasons'] = [];
                $u['display_name'] = !empty($u['name']) ? $u['name'] : $u['nickname'];

                $email_lc = strtolower($u['email']);
                $domain = explode('@', $email_lc)[1] ?? '';

                // 1. Blockliste Check
                foreach ($disallowed_array as $pattern) {
                    if ($email_lc === $pattern || $domain === $pattern) {
                        $u['spam_score'] += 100;
                        $u['spam_reasons'][] = "Blockliste: " . $pattern;
                        break;
                    }
                }

                // 2. TLD Check
                if (preg_match('/\.(top|xyz|bid|buzz|monster|pw|tk)$/i', $u['email'], $matches)) {
                    $u['spam_score'] += 60;
                    $u['spam_reasons'][] = "TLD: ." . $matches[1];
                }

                // 3. Zahlenfolge Check
                if (preg_match('/[0-9]{4,}/', $u['nickname'], $matches)) {
                    $u['spam_score'] += 30;
                    $u['spam_reasons'][] = "Zahlenfolge: " . $matches[0];
                }

                // NUR hinzufügen, wenn Score >= 30
                if ($u['spam_score'] >= 30) {
                    $filteredUsers[] = $u;
                }
            }
        }

        // Sortierung der gefilterten Liste
        usort($filteredUsers, function($a, $b) use ($sort_by, $order) {
            $valA = $a[$sort_by] ?? '';
            $valB = $b[$sort_by] ?? '';
            return ($order === 'asc') ? ($valA <=> $valB) : ($valB <=> $valA);
        });

        // Paginierung der gefilterten Liste
        $total_filtered = count($filteredUsers);
        $pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 50);
        $offset = $pager->getStart();
        $pagedUsers = array_slice($filteredUsers, $offset, 50);

        $hilfe_tpl = Renderer::getMarkupTemplate('hilfe.tpl', 'addon/guardian');
        $hilfe_html = Renderer::replaceMacros($hilfe_tpl, []);
        $t = Renderer::getMarkupTemplate('guardian.tpl', 'addon/guardian');

        return Renderer::replaceMacros($t, [
            '$title' => 'Guardian Spam Audit',
            '$count' => $total_filtered,
            '$users' => $pagedUsers,
            '$sort_url' => DI::baseUrl() . '/guardian',
            '$current_sort' => $sort_by,
            '$next_order' => $next_order,
            '$pager' => $pager->renderFull($total_filtered),
            '$hilfe' => $hilfe_html,
        ]);
    }
}
