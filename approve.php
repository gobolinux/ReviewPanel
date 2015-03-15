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
header("Content-type: text/html");
extract(getmeta($_REQUEST['source']));
$source = $_REQUEST['source'];
$commenting = false;
if (authorised()) {
	if ($_POST['approve'] || $_POST['approve_and_close'])
		$status = 'approve';
	elseif ($_POST['approve-manual'])
		$status = 'approve';
	elseif ($_POST['reject'])
		$status = 'reject';
	elseif ($_POST['claim'])
		$status = 'claimed';
	elseif ($_POST['comment'])
		$commenting = true;
	elseif ($status != 'claimed' || $_POST['unclaim'])
		$status = 'pending';
	file_put_contents("$recipedir/{$_REQUEST['source']}/status", $status);
	file_put_contents("$recipedir/{$_REQUEST['source']}/reviewer", 
		$_SESSION['email']);
	file_put_contents("$recipedir/{$_REQUEST['source']}/message", 
		$_POST['message']);
	$msg = "You recently submitted a GoboLinux recipe for $program $version,".
	       " which has now been reviewed by " . username($_SESSION['email']) .
	       ". ";
	if ('approve' == $status) {
		$msg .= "Your recipe has been approved and will appear in the ".
			"public store soon. ";
		if (count($modified))
			$msg .= "The reviewer made some changes before committing, " . 
				"which you may review for the future at " .
			 	"<http://{$_SERVER['HTTP_HOST']}$url>";
	}
	if ('approve' == $status && !$_POST['approve-manual']) {
		$pwd = getcwd();
		echo "<pre>";
		chdir("$recipesvn/trunk");
		system("svn checkout $recipesvnserv/trunk/$program");
		if (!file_exists("$program")) {
			echo "This is a new program, creating directory...<br />";
			system("svn -m 'Creating trunk program directory' " .
			"mkdir --no-auth-cache --non-interactive " . 
			"--username $SVN_USER --password $SVN_PASS " .
			"$recipesvnserv/trunk/$program");
			system("svn checkout $recipesvnserv/trunk/$program");
			system("cp -R " .
				"$pwd/$recipedir/{$_REQUEST['source']}/$program/$version " .
				"$recipesvn/trunk/$program");
			system("svn add --force $program/*");
		} else {
			chdir("$program");
			system('svn up');
			list($originver, $origenrev) = explode('-', $origin);
			if ('none' == $originver) {
				echo "It seems this program has been added to the tree " .
				"since this version was submitted. It will not be possible " .
				"to commit this recipe. It must be submitted again based " .
				"on the entry in trunk.";
				exit();
			}
			if (!file_exists("$version")) {
				echo "This version does not exist in trunk, copying from " .
				"$originver and updating...<br />";
				system("svn cp $originver $version");
				system("find $version -name .svn -prune -o -type f -exec rm '{}' ';'");
				system("cp -Rf " .
					"$pwd/$recipedir/$source/$program/$version .");
			} else {
				system("find $version -name .svn -prune -o -type f -exec rm '{}' ';'");
				system("cp -Rf $pwd/$recipedir/$source/$program/$version .");
			}
			system("svn st | awk '/!/ {print \$2}' | xargs svn rm");
			system('svn add --force *');
		}
		
		chdir("$recipesvn/revisions");
		system("svn checkout $recipesvnserv/revisions/$program");
		if (!file_exists("$program")) {
			echo "Creating revisions/$program directory...<br />\n";
			system("svn -m 'Creating revisions program directory' " .
			"mkdir --no-auth-cache --non-interactive " . 
			"--username $SVN_USER --password $SVN_PASS " .
			"$recipesvnserv/revisions/$program");
			$nextrev = 1;
		} else {
			chdir($program);
			system('svn up');
			$nextrev = 1;
			$d = dir(".");
			while (false !== ($entry = $d->read())) {
				if (substr($entry, 0, strlen($version)) != $version)
					continue;
				list($junk, $rev) = explode('-r', $entry);
				if ((0+$rev) >= ($nextrev))
					$nextrev = 1+$rev;
			}
		}
		echo "Committing trunk of $program $version...<br />";
		chdir("$recipesvn/trunk/$program");
		
		$submitter = preg_replace('/[^-a-zA-Z0-9 _]/', '', $submitter);
		
		#echo "pwd: " . getcwd() . "<br />\n";
		#echo "svn commit --no-auth-cache --non-interactive --username ". 
		#	"$SVN_USER --password $SVN_PASS -m " .
		#	escapeshellarg("Recipe for $program $version submitted by " . 
		#	"$submitter reviewed by " . username($_SESSION['email']) . 
		#	" in the recipe review panel") . "<br />\n";
		system("svn commit --no-auth-cache --non-interactive --username ". 
			"$SVN_USER --password $SVN_PASS -m " .
			escapeshellarg("Recipe for $program $version submitted by " . 
			"$submitter reviewed by " . username($_SESSION['email']) . 
			" in the recipe review panel"));
		
		echo "Creating revision $program $version-r$nextrev...<br />";
		
		system("svn cp --no-auth-cache --non-interactive --username " . 
			"$SVN_USER --password $SVN_PASS -m " .
			escapeshellarg("Committing revision $nextrev of $program " . 
			"$version") .
			" $recipesvnserv/trunk/$program/$version " . 
			"$recipesvnserv/revisions/$program/$version-r$nextrev");
		echo "Committed recipe to store.<br/>";
		echo "</pre>";
	} elseif ('reject' == $status) {
		$msg .= "The recipe you sent to GoboLinux for $program " .
			"version $version was not accepted.\n\n" .
			"Reviewer: " . username($_SESSION['email']);
	} elseif ($commenting) {
		$msg = "You submitted a GoboLinux recipe for $program " .
			"version $version. The recipe is still pending " .
			"review, but the reviewer left a comment:\n";
		$msg .= $_POST['message'];
		$msg .= "\n\nThis submission is accessible on the web at" .
			"<http://{$_SERVER['HTTP_HOST']}$url>";
		mail("$submitter <$submitteremail>, gobolinux-recipes@lists.gobolinux.org",
		     "Comments on recipe $program $version submitted at " .
			date('Y-m-d', $date),
		     wordwrap($msg, 72),
		     "From: {$_SESSION['email']}\r\n" .
		     "Reply-To: gobolinux-recipes@lists.gobolinux.org\r\n" .
		     "Content-Type: text/plain; format=flowed\r\n"
		);
	}
	if (('claimed' != $status) && ('pending' != $status)) {
		if ($_POST['message']) {
			$msg .= "\n\nReviewer comment:\n";
			$msg .= $_POST['message'];
		}
		mail("$submitter <$submitteremail>",
		     "Recipe Review: $program $version",
		     wordwrap($msg, 72),
		     "From: noreply@{$_SERVER['HTTP_HOST']}\r\n" .
		     "Reply-To: {$_SESSION['email']}, " .
		               "gobolinux-recipes@lists.gobolinux.org\r\n" .
		     "Content-Type: text/plain; format=flowed\r\n"
		);
	}
	
	
	header("Refresh: 10;url=$webroot");
?>
The recipe has been marked: <?php echo $status?>
<?php if ($_POST['approve_and_close']) {?>
<script type="text/javascript">
setTimeout(function() {window.close();}, 2000);
window.close();
</script>
<?php
}
?>
<?php
if (false) {
?><br />
Note that you will still need to commit it manually. If you have not done
this, you may <a href="<?php
echo "$tarball"
?>">download the tarball</a>, or run:<br /><code>PutRecipe <?php echo "http://{$_SERVER['HTTP_HOST']}$tarball"?></code>
<?php
} # if false
} else
	echo "You are not authorised to approve or reject a recipe.";
