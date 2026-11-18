<?php

namespace wcf\system\cs2admin;

use wcf\system\WCF;

/**
 * Synchronisiert Forum-Benutzergruppen mit der CS2-SimpleAdmin-Datenbank.
 */
final class Cs2AdminSyncer
{
    public const FLAGS = [
        '@css/reservation', '@css/generic', '@css/kick', '@css/ban', '@css/unban',
        '@css/vip', '@css/slay', '@css/changemap', '@css/cvar', '@css/config',
        '@css/chat', '@css/vote', '@css/password', '@css/rcon', '@css/cheats', '@css/root',
    ];

    /**
     * @return array<int, array{priority:int, immunity:int, flags:string[]}>
     */
    public static function getStoredRows(): array
    {
        $rows = [];
        $stmt = WCF::getDB()->prepareStatement("SELECT groupID, priority, immunity, flags FROM wcf1_cs2admin_group");
        $stmt->execute();
        while ($r = $stmt->fetchArray()) {
            $rows[(int)$r['groupID']] = [
                'priority' => (int)$r['priority'],
                'immunity' => (int)$r['immunity'],
                'flags' => \array_values(\array_filter(\explode(',', (string)$r['flags']))),
            ];
        }

        return $rows;
    }

    public static function toSteam64(string $in): ?string
    {
        $in = \trim($in);
        if (\preg_match('/^7656119\d{10}$/', $in)) {
            return $in;
        }
        if (\preg_match('/^STEAM_[0-5]:([01]):(\d+)$/', $in, $m)) {
            return (string)(76561197960265728 + (int)$m[2] * 2 + (int)$m[1]);
        }
        if (\preg_match('#steamcommunity\.com/profiles/(7656119\d{10})#', $in, $m)) {
            return $m[1];
        }

        return null;
    }

    private function getSteamColumn(): string
    {
        $stmt = WCF::getDB()->prepareStatement("SELECT optionID FROM wcf1_user_option WHERE optionName = ?");
        $stmt->execute([CS2ADMIN_STEAM_OPTION]);
        $id = (int)$stmt->fetchSingleColumn();
        if ($id <= 0) {
            throw new \RuntimeException("Profilfeld '" . CS2ADMIN_STEAM_OPTION . "' nicht gefunden.");
        }

        return 'userOption' . $id;
    }

    private function connect(): \PDO
    {
        $dsn = \sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            CS2ADMIN_DB_HOST,
            (int)CS2ADMIN_DB_PORT,
            CS2ADMIN_DB_NAME
        );
        $db = new \PDO($dsn, CS2ADMIN_DB_USER, CS2ADMIN_DB_PASSWORD, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);

        $need = [
            'sa_admins' => ['player_steam_id', 'player_name', 'immunity', 'ends', 'created'],
            'sa_admins_flags' => ['admin_id', 'flag', 'server_id'],
        ];
        foreach ($need as $table => $cols) {
            try {
                $have = $db->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(\PDO::FETCH_COLUMN);
            } catch (\PDOException $e) {
                throw new \RuntimeException("Tabelle {$table} nicht gefunden.");
            }
            $missing = \array_diff($cols, $have);
            if ($missing) {
                throw new \RuntimeException("Tabelle {$table}: Spalten fehlen (" . \implode(', ', $missing) . ').');
            }
        }

        return $db;
    }

    /**
     * @return array{count:int, skipped:int}
     */
    public function sync(): array
    {
        $rows = self::getStoredRows();
        if ($rows === []) {
            throw new \RuntimeException('Keine Gruppen konfiguriert.');
        }
        \uasort($rows, static fn ($a, $b) => $b['priority'] <=> $a['priority']);

        $col = $this->getSteamColumn();
        $stmt = WCF::getDB()->prepareStatement(
            "SELECT u.username, v.{$col} AS steam
             FROM wcf1_user_to_group ug
             JOIN wcf1_user u ON u.userID = ug.userID
             LEFT JOIN wcf1_user_option_value v ON v.userID = u.userID
             WHERE ug.groupID = ? AND u.banned = 0"
        );

        $admins = [];
        $skipped = 0;
        foreach ($rows as $groupID => $cfg) {
            $stmt->execute([$groupID]);
            while ($r = $stmt->fetchArray()) {
                $id = self::toSteam64((string)$r['steam']);
                if ($id === null) {
                    $skipped++;
                    continue;
                }
                if (isset($admins[$id])) {
                    continue; // höchste Gruppe gewinnt
                }
                $name = \preg_replace('/[\x00-\x1F]/u', '', (string)$r['username']);
                $admins[$id] = [
                    'name' => \mb_substr($name, 0, 64),
                    'immunity' => $cfg['immunity'],
                    'flags' => $cfg['flags'],
                ];
            }
        }

        // Sicherheitsnetz: nie eine leere Liste schreiben
        if ($admins === []) {
            throw new \RuntimeException('Keine Admins mit gültiger Steam-ID gefunden, Abbruch.');
        }

        $db = $this->connect();
        $db->beginTransaction();
        try {
            $db->exec('DELETE FROM sa_admins_flags');
            $db->exec('DELETE FROM sa_admins');
            $insA = $db->prepare('INSERT INTO sa_admins (player_steam_id, player_name, immunity, ends, created) VALUES (?, ?, ?, NULL, NOW())');
            $insF = $db->prepare('INSERT INTO sa_admins_flags (admin_id, flag, server_id) VALUES (?, ?, NULL)');
            foreach ($admins as $steam => $a) {
                $insA->execute([(string)$steam, $a['name'], $a['immunity']]);
                $adminId = (int)$db->lastInsertId();
                foreach ($a['flags'] as $flag) {
                    $insF->execute([$adminId, $flag]);
                }
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        return ['count' => \count($admins), 'skipped' => $skipped];
    }
}
