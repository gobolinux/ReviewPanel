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

echo "<a href=\"$url\">Back to diff</a>";
$file = $_REQUEST['file'];
if (false !== strpos($file, '..'))
	exit();
$title = "Edit: $program/$version/$file";
if ($_POST['save'] && authorised()) {
	# Save a copy of the old file so we can diff against it
	copy("$recipedir/{$_REQUEST['source']}/$program/$version/$file", "$recipedir/{$_REQUEST['source']}/$program/$version/$file.old");
	file_put_contents("$recipedir/{$_REQUEST['source']}/$program/$version/$file", str_replace("\r", "", sslash($_POST['data'])));
	# Save the directory so we can come back later. We change to make the commands easier.
	$pwd = getcwd();
	$time = time();
	chdir("$recipedir/{$_REQUEST['source']}");
	system("diff --strip-trailing-cr -urNa $program/$version/$file.old $program/$version/$file > $time.patch");
	unlink("$program/$version/$file.old");
	system("tar cjf $program--$version--recipe.tar.bz2 $program");
	chdir($pwd);
	if (file_exists("$recipedir/{$_REQUEST['source']}/modified"))
		$modified = file("$recipedir/{$_REQUEST['source']}/modified");
	else
		$modified = array();
	$modified[] = "$file $time {$_SESSION['email']}";
	file_put_contents("$recipedir/{$_REQUEST['source']}/modified", join("\n", $modified));
}

$cont = file_get_contents("$recipedir/{$_REQUEST['source']}/$program/$version/$file");
$lines = explode("\n", $cont);
$rows = substr_count(wordwrap($cont, 80), "\n") + 1;
?>
<form method="post" action="<?php echo $url?>/edit/<?php echo htmlspecialchars($file)?>">
 <textarea name="data" rows="<?php echo $rows?>" cols="80"><?php echo htmlspecialchars($cont)?></textarea>
 <br />
<?php if (authorised()) {?>
 <input type="submit" name="save" value="Save" />
<?php }?>
</form>
