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
require_once "Text/Diff.php";
require_once "Text/Diff/Renderer/inline.php";

extract(getmeta($_REQUEST['source']));

$title = "Diff: $program $version";

function format_diff($patch) {
	global $deletedcolour, $newcolour, $replacedcolour, $replacementcolour;

	$d = file($patch);
	$left = array();
	$right= array();
	foreach ($d as $x) {
		if (substr($x, 0, 4) == '--- ' || substr($x, 0, 4) == '+++ ')
			continue;
		$char = substr($x, 0, 1);
		$line = substr($x, 1);
		if ('-'==$char) {
			$left[] = $line;
		} elseif ('+'==$char) {
			$right[] = $line;
		} else {
			if (count($left)>count($right))
				for ($i=count($right);$i<count($left);$i++)
					$right[] = '';
			if (count($left)<count($right))
				for ($i=count($left);$i<count($right);$i++)
					$left[] = '';
			if (' '==$char) {
				$left[] = $line;
				$right[] = $line;
			} else {
				$left[] = array($x);
				$right[] = array($x);
			}
		}
	}
	$n = max(count($left), count($right));
	echo "<table cellpadding=\"0\" cellspacing=\"0\">\n";
	echo '<tr><th width="50%">Original</th><th width="50%">New</th></tr>' . "\n";
	for ($i=0; $i<$n; $i++) {
		$ol = @htmlspecialchars($left[$i]);
		$or = @htmlspecialchars($right[$i]);
		$l = $left[$i];
		$r = $right[$i];
		$l = preg_replace('/^( +)/em', 'str_repeat("&nbsp;", strlen("\\1"))', $l);
		$r = preg_replace('/^( +)/em', 'str_repeat("&nbsp;", strlen("\\1"))', $r);
		$ol = preg_replace('/^( +)/em', 'str_repeat("&nbsp;", strlen("\\1"))', $ol);
		$or = preg_replace('/^( +)/em', 'str_repeat("&nbsp;", strlen("\\1"))', $or);
		$d = new Text_Diff('auto', array(array($l), array($r)));
		$ren = new Text_Diff_Renderer_inline();
		$res = $ren->render($d);
		$res = str_replace("&amp;nbsp;", "&nbsp;", $res);
		$l = preg_replace("!<ins>(.+?)</ins>!", "", $res);
		$r = preg_replace("!<del>(.+?)</del>!", "", $res);
		echo "<tr>";
		if (is_array($left[$i]))
			echo "<td colspan=\"2\" style=\"background: #888; color: white; border-top: 2px solid black;border-bottom: 1px solid black;\">{$left[$i][0]}</td>";
		elseif ($ol==$or)
			echo "<td style=\"border-top: 1px solid #888;\">$ol</td><td style=\"border-top: 1px solid #888;\">$or</td>";
		elseif (''==$ol)
			echo "<td style=\"border-top: 1px solid #888;\">$l</td><td class=\"new\" style=\"border-top: 1px solid #888;\">$or</td>";
		elseif (''==$or)
			echo "<td class=\"deleted\" style=\"border-top: 1px solid #888;\">$ol</td><td style=\"border-top: 1px solid #888;\">$r</td>";
		else
			echo "<td class=\"replaced\" style=\"border-top: 1px solid #888;\">$l</td><td class=\"replacement\" style=\"border-top: 1px solid #888;\">$r</td>";
		echo "</tr>\n";
	}
	echo "</table>";
}
$reviewername = username($reviewer);
if ('claimed' == $status)
	echo "<div style=\"color: red; font-weight: bold; font-size: 20pt;\">" .
		"This recipe is claimed for review by $reviewername</div>\n";
if (filesize("$location/changes.patch")>0)
	format_diff("$location/changes.patch");
else
	format_diff("$location/entire.patch");

if (count($modified) > 0) {
	echo "The following files have been modified from this panel since submission:";
	echo "<ul>";
	foreach ($modified as $mod) {
		list($file, $patch, $author) = explode(' ', $mod);
		echo "<li>$file by ".username($author);
		format_diff("$location/$patch.patch");
		echo "</li>\n";
	}
	echo "</ul>";
}
?>
<h2>Files in this recipe</h2>
<ul>
<?php
foreach ($files as $file) {
?>
 <li><a href="<?php echo $url?>/edit/<?php echo htmlspecialchars($file)?>"><?php echo htmlspecialchars($file)?></a></li>
<?php
}
?>
</ul>
<p><a href="<?php echo $tarball?>">Recipe tarball</a></p>
<?php
if (!in_array("Resources/Description", $files)) {
    echo "<p class=\"warning\">No Resources/Description file.</p>";
} else {
    $fc = file_get_contents("$location/$program/$version/Resources/Description");
    if (strpos($fc, "[License]") === false || strpos($fc, "[Description]") === false) {
        echo "<p class=\"warning\">Invalid Resources/Description file.</p>";
    }
}
if (!in_array("Resources/Dependencies", $files)) {
    echo "<p class=\"warning\">No Resources/Dependencies file.</p>";
}
if (!in_array("Resources/Description", $files)) {
    echo "<p class=\"warning\">No Resources/Description file.</p>";
}
if (strpos(file_get_contents("$location/entire.patch"), "do_patch()")) {
    echo "<p class=\"warning\">Recipe overrides do_patch.</p>";
}
if (strpos(file_get_contents("$location/entire.patch"), "sandbox_options=")) {
    echo "<p class=\"warning\">Recipe uses sandbox_options.</p>";
}
?>
<h2>Status</h2>
<p>Currently marked as: <strong><?php echo $status?></strong> <?php
if ($reviewer)
	echo "by ". username($reviewer);
?></p>
<?php
if ($message)
	echo "The message was: <blockquote>" . nl2br($message) . "</blockquote>";
if ($old) {
    echo "This submission is expired and now inalterable.";
} else {
?>
<?php if ($submitteremail == $_SESSION['email']) {?>
<form action="<?php echo $url?>/delete" method="post">
 <input type="submit" name="delete" value="Delete this submission" />
</form>
<?php } elseif (!$_SESSION['email']) {?>
If you are the submitter of this recipe, you may <a href="<?php echo $webroot?>/auth/login">log in</a> to rescind this submission.
<?php }?>
<?php if (authorised()) {?>
<form action="<?php echo $url?>/approve" method="post">
 <input type="submit" name="approve" value="Mark as approved" />
 <input type="submit" name="reject" value="Mark as rejected" />
 <input type="submit" name="approve-manual" value="Approve for manual commit" />
<?php if(false){?> <input type="submit" name="approve_and_close" value="Approve and close window" /><?php }?>
 <?php if ('claimed' == $status) {?>
 <input type="submit" name="unclaim" value="Unmark as claimed for review" />
 <?php } else {?>
 <input type="submit" name="claim" value="Mark as claimed for review" />
 <?php }?>
 <input type="submit" name="comment" value="Send a comment to the submitter" /><br />
 <label for="message">Approval/rejection message (optional): </label><br /><textarea name="message" id="message" rows="5" cols="80"></textarea>
</form>
<?php
}
}
