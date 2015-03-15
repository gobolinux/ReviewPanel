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

$d = dir('.');
$time = file_exists('reviewpanel.tar.bz2') ? filemtime('reviewpanel.tar.bz2') : 0;
while (false !== ($entry = $d->read())) {
	if (('.' == $entry) || ('..' == $entry) || ('reviewpanel.tar.bz2' == $entry))
		continue;
	if (filemtime($entry) > $time) {
		chdir('..');
		$dir = basename($webroot);
		if (is_link($dir))
			$dir = readlink($dir);
		system("tar --exclude reviewpanel.tar.bz2 --exclude files --exclude git --exclude '*~' -cjhvf $dir/reviewpanel.tar.bz2 $dir");
	}
}

ob_end_clean();
header('Location: reviewpanel.tar.bz2');
exit();
