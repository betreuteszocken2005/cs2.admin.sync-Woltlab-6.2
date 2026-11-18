<?php

namespace wcf\acp\form;

use wcf\data\user\group\UserGroup;
use wcf\form\AbstractForm;
use wcf\system\cs2admin\Cs2AdminSyncer;
use wcf\system\WCF;

class Cs2AdminGroupsForm extends AbstractForm
{
    public $activeMenuItem = 'wcf.acp.menu.link.cs2admin.groups';

    public $neededPermissions = ['admin.cs2admin.canManage'];

    /** @var UserGroup[] */
    public $groups = [];

    /** @var array<int, array{priority:int, immunity:int, flags:string[]}> */
    public $rows = [];

    public $doSync = false;

    public $syncResult = null;

    public $syncError = '';

    public function readParameters()
    {
        parent::readParameters();

        // "Alle", "Gäste" und "Benutzer" sind absichtlich nicht wählbar
        $this->groups = UserGroup::getGroupsByType(
            [],
            [UserGroup::EVERYONE, UserGroup::GUESTS, UserGroup::USERS]
        );

        if (empty($_POST)) {
            $this->rows = Cs2AdminSyncer::getStoredRows();
        }
    }

    public function readFormParameters()
    {
        parent::readFormParameters();

        $this->doSync = !empty($_POST['sync']);
        $this->rows = [];
        $posted = (isset($_POST['rows']) && \is_array($_POST['rows'])) ? $_POST['rows'] : [];

        foreach ($this->groups as $groupID => $group) {
            $r = $posted[$groupID] ?? null;
            if (!\is_array($r) || empty($r['enabled'])) {
                continue;
            }
            $flags = [];
            if (isset($r['flags']) && \is_array($r['flags'])) {
                $flags = \array_values(\array_intersect(
                    Cs2AdminSyncer::FLAGS,
                    \array_filter($r['flags'], 'is_string')
                ));
            }
            $this->rows[(int)$groupID] = [
                'priority' => (int)($r['priority'] ?? 0),
                'immunity' => \max(0, \min(255, (int)($r['immunity'] ?? 0))),
                'flags' => $flags,
            ];
        }
    }

    public function save()
    {
        parent::save();

        $db = WCF::getDB();
        $db->beginTransaction();
        try {
            $db->prepareStatement("DELETE FROM wcf1_cs2admin_group")->execute();
            $stmt = $db->prepareStatement(
                "INSERT INTO wcf1_cs2admin_group (groupID, priority, immunity, flags) VALUES (?, ?, ?, ?)"
            );
            foreach ($this->rows as $groupID => $r) {
                $stmt->execute([$groupID, $r['priority'], $r['immunity'], \implode(',', $r['flags'])]);
            }
            $db->commitTransaction();
        } catch (\Throwable $e) {
            $db->rollBackTransaction();
            throw $e;
        }

        $this->saved();

        if ($this->doSync) {
            try {
                $this->syncResult = (new Cs2AdminSyncer())->sync();
            } catch (\Throwable $e) {
                $this->syncError = $e->getMessage();
            }
        }

        WCF::getTPL()->assign('success', true);
    }

    public function assignVariables()
    {
        parent::assignVariables();

        $view = [];
        foreach ($this->groups as $groupID => $group) {
            $row = $this->rows[$groupID] ?? null;
            $flags = [];
            foreach (Cs2AdminSyncer::FLAGS as $flag) {
                $flags[$flag] = $row !== null && \in_array($flag, $row['flags'], true);
            }
            $view[$groupID] = [
                'enabled' => $row !== null,
                'priority' => $row['priority'] ?? 0,
                'immunity' => $row['immunity'] ?? 0,
                'flags' => $flags,
            ];
        }

        WCF::getTPL()->assign([
            'groups' => $this->groups,
            'view' => $view,
            'allFlags' => Cs2AdminSyncer::FLAGS,
            'syncResult' => $this->syncResult,
            'syncError' => $this->syncError,
        ]);
    }
}
