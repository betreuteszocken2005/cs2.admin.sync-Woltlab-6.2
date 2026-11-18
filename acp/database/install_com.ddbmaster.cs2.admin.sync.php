<?php

use wcf\system\database\table\column\NotNullInt10DatabaseTableColumn;
use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\column\TextDatabaseTableColumn;
use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\index\DatabaseTableForeignKey;
use wcf\system\database\table\index\DatabaseTableIndex;
use wcf\system\database\table\index\DatabaseTablePrimaryIndex;

return [
    DatabaseTable::create('wcf1_cs2admin_group')
        ->columns([
            ObjectIdDatabaseTableColumn::create('entryID'),
            NotNullInt10DatabaseTableColumn::create('groupID'),
            NotNullInt10DatabaseTableColumn::create('priority')->defaultValue(0),
            NotNullInt10DatabaseTableColumn::create('immunity')->defaultValue(0),
            TextDatabaseTableColumn::create('flags'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()->columns(['entryID']),
            DatabaseTableIndex::create('groupID')->type(DatabaseTableIndex::UNIQUE_TYPE)->columns(['groupID']),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['groupID'])
                ->referencedTable('wcf1_user_group')
                ->referencedColumns(['groupID'])
                ->onDelete('CASCADE'),
        ]),
];
