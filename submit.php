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
require 'config.php';

$data = file("php://input");

$headers = array();
$entire = "";
$changes = "";
for ($i=0; $i< count($data); $i++)
	if ("\n" !== $data[$i]) {
		list($header, $value) = explode(':', trim($data[$i]), 2);
		$headers[$header] = substr($value, 1);
	} else
		for ($i++; $i<count($data); $i++)
			if ("\n" !== $data[$i])
				$entire.=$data[$i];
			else
				for ($i++; $i<count($data); $i++)
						$changes.=$data[$i];

$entire = base64_decode($entire);
$changes = base64_decode($changes);

if (!strpos("$entire", "Resources/Description")) {
	ob_end_clean();
	print "Error: Please provide a completed Resources/Description file in the recipe.";
	exit();
}

$submitter = $headers['Submitter'];
$program = $headers['Program'];
$version = $headers['Version'];

if (preg_match("![./][./]!", "$submitter$program$version") || preg_match("/^\-\-\-.*\n\+\+\+.*\.\./m", $entire)) {
	print "Security error.";
	exit;
}

if (preg_match("/^(.+?) <(([^>@]+)@[^>]+?)>$/", $submitter, $m)) {
	$submitter = $m[1];
	$submitteremail = $m[2];
} else {
	ob_end_clean();
	print "Error: Please fill in compileRecipeAuthor in /System/Settings/Compile/Compile.conf using the format \"Your Name <your@email.address>\"";
	exit();
}
$submitterdir = time() . '-' . getmypid() . '/' . preg_replace("![^a-z0-9_-]!", '', strtolower(str_replace(' ', '_', $submitter)));
$savedir = "$recipedir/" . $submitterdir;
mkdir("$savedir", 0755, true);
chdir($savedir);
file_put_contents('entire.patch', $entire);
file_put_contents('changes.patch', $changes);
file_put_contents('submitter', $submitter);
file_put_contents('submitteremail', $submitteremail);
file_put_contents('program', $program);
file_put_contents('version', $version);
file_put_contents('origin', $headers['Origin']);
file_put_contents('status', 'pending');
file_put_contents('modified', '');
system("patch -p0 < entire.patch>/dev/null");
if (!file_exists($program)) {
	mkdir("$program");
	system("cp -R empty-*/$program $program/$version");
}
system("find $program -type f | cut -d/ -f3,4,5 > files");
system("tar cjf $program--$version--recipe.tar.bz2 $program");

ob_end_clean();
print "Success. Received ".strlen($changes)." bytes of changes and ".strlen($entire)." bytes total data. Saved as http://$host$webroot/$submitterdir/$program/$version\n";
exit();
