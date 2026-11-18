<?php

namespace wcf\system\cronjob;

use wcf\data\cronjob\Cronjob;
use wcf\system\cs2admin\Cs2AdminSyncer;

class Cs2AdminSyncCronjob extends AbstractCronjob
{
    public function execute(Cronjob $cronjob)
    {
        parent::execute($cronjob);

        (new Cs2AdminSyncer())->sync();
    }
}
