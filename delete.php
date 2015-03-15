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
extract(getmeta($_REQUEST['source']));
$source = $_REQUEST['source'];
$commenting = false;

if ($_POST['delete'] && $submitteremail == $_SESSION['email']) {
	system("rm -rf $recipedir/{$_REQUEST['source']}");
	list($dir, $junk) = explode('/', $_REQUEST['source']);
	$h = dir("$recipedir/$dir");
	$count = 0;
	while (false !== ($entry = $h->read())) {
		if (substr($entry, 0, 1) == '.') continue;
		$count = 1;
		break;
	}
	if (!$count) {
		system("rm -rf $recipedir/$dir");
	}
	echo "Recipe submission deleted.";
	header("Refresh: 10;url=$webroot");
} else
	echo "You are not authorised to delete this recipe.";
