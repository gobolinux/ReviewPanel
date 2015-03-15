<?php
# Copyright (C) 2008 Michael Homer
# 
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
# 
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
if (!authorised()) {
    print "You are not authorised to do this.";
    exit();
}

$pendings = array();
$approveds = array();
$rejecteds = array();
$claimeds = array();
$submissiondir = dir($recipedir);
while (false !== ($entry = $submissiondir->read())) {
    if (substr($entry, 0, 1) == '.') continue;
    if ($entry == 'old') continue;  
    $s = dir("$recipedir/$entry");
    while (false !== ($subdir = $s->read())) {
        if (substr($subdir, 0, 1) == '.') continue;
        $meta = getmeta("$entry/$subdir");
        list($time, $junk) = explode('-', $entry);
        if ('pending' == $meta['status'])
            $pendings[$time] = $meta;
        elseif ('approve' == $meta['status'])
            $approveds[$time] = $meta;
        elseif ('reject' == $meta['status'])
            $rejecteds[$time] = $meta;
        elseif ('claimed' == $meta['status'])
            $claimeds[$time] = $meta;
    }
}

krsort($approveds);
krsort($rejecteds);

function moveItems($entries, $datethreshold=0, $mindisplayed=0) {
    global $recipedir;
    $count = 0;
    $archived = 0;
    foreach ($entries as $time=>$ent) {
        $count++;
        if ($count > $mindisplayed) {
            echo "Archiving $ent[program] $ent[version] from $ent[submitter]...<br />\n";
            $td = dirname($ent['location']);
            echo "mv $td $recipedir/old<br />\n";
            system("mv $td $recipedir/old");
            $archived++;
        }
    }
    echo "<strong>$archived archived.</strong>";
}

echo "<h2>Approveds</h2>";
moveItems($approveds, $datethreshold, 10);
echo "<h2>Rejecteds</h2>";
moveItems($rejecteds, $datethreshold, 10);
