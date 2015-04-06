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
$pendings = array();
$approveds = array();
$rejecteds = array();
$claimeds = array();
$submissiondir = dir($recipedir);
$skipemails = array();
if ($_GET['skipemails'])
	$skipemails = $_GET['skipemails'];
while (false !== ($entry = $submissiondir->read())) {
	if (substr($entry, 0, 1) == '.') continue;
	if ($entry == 'old' || ! is_dir("$recipedir/$entry")) continue;	
	$s = dir("$recipedir/$entry");
	while (false !== ($subdir = $s->read())) {
		if (substr($subdir, 0, 1) == '.') continue;
		$meta = getmeta("$entry/$subdir");
		list($time, $junk) = explode('-', $entry);
		if ('pending' == $meta['status'] &&
		    !in_array($meta['submitteremail'], $skipemails))
			$pendings[$time] = $meta;
		elseif ('approve' == $meta['status'])
			$approveds[$time] = $meta;
		elseif ('reject' == $meta['status'])
			$rejecteds[$time] = $meta;
		elseif ('claimed' == $meta['status'])
			$claimeds[$time] = $meta;
	}
}

function listItems($entries, $datethreshold=0, $mindisplayed=0) {
	$count = 0;
	echo '<ol>';
	foreach ($entries as $time=>$ent) {
		$count++;
		if (($time < $datethreshold) && (!$mindisplayed || $count > $mindisplayed)) {
			break;
		}
		if ($ent['reviewer'])
			$stat = " reviewed by " . username($ent['reviewer']);
		else
			$stat = '';
		echo "<li><a href=\"$ent[url]\">$ent[program] $ent[version] from $ent[submitter]</a> at " . date("Y-m-d H:i:s", $time) . "$stat (<a href=\"$ent[tarball]\">tarball</a>)</li>\n";
	}
	echo '</ol>';
}

function sort_by_name($a, $b) {
    return strcmp($a['program'], $b['program']);
}
ksort($pendings);
if ($_GET['order'] == 'name') {
   uasort($pendings, 'sort_by_name');
}
if (count($pendings)) {
	print "<h2>Pending submissions</h2>\n";
	listItems($pendings);
} else {
	print "No pending submissions.";
}

if (count($claimeds)) {
	print "<h2>Pending submissions claimed for review</h2>\n";
	listItems($claimeds);
}

# Two days ago
$datethreshold = time() - 86400 * 2;

if (count($approveds)) {
	krsort($approveds);
	print "<h2>Recently approved submissions</h2>\n";
	listItems($approveds, $datethreshold, 10);
}
if (count($rejecteds)) {
	krsort($rejecteds);
	print "<h2>Recently rejected submissions</h2>\n";
	listItems($rejecteds, $datethreshold, 10);
}
