#!/bin/sh
# Baut das installierbare WoltLab-Paket: com.ddbmaster.cs2.admin.sync.tar
set -e
cd "$(dirname "$0")"
tar -cf files.tar -C files lib
(cd acptemplates && tar -cf ../acptemplates.tar *.tpl)
tar -cf com.ddbmaster.cs2.admin.sync.tar \
    package.xml files.tar acptemplates.tar option.xml userGroupOption.xml \
    acpMenu.xml cronjob.xml acp language
rm -f files.tar acptemplates.tar
echo "Fertig: com.ddbmaster.cs2.admin.sync.tar"
