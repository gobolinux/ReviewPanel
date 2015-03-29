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

require("Sajax.php");

function log_system($command) {
	global $logfile;
	system("( $command ) 2>&1 >> $logfile");
}

function log_echo($str) {
	global $logfile;
	file_put_contents($logfile, $str, FILE_APPEND);
}

function refresh($src) {
	global $recipedir, $source, $logfile;
	error_reporting(E_ERROR);
	if (!authorised()) {
		return "Not authorised.";
	}
	$source = $src;
	$pwd = getcwd();
	$logfile = "$pwd/$recipedir/$source/upload_log";
	$fd = fopen($logfile, "r");
	if ($fd === false) {
		return "Please wait...";
	}
	$out = "";
	while (!feof($fd)) {
		$out .= fread($fd, 1024);
	}
	fclose($fd);
	return $out;
}

function upload($src) {
	global $recipedir, $source, $recipegit, $program, $version;
	global $submitter, $submitteremail, $origin, $logfile;
	error_reporting(E_ERROR);
	if (!authorised()) {
		return;
	}
	$source = $src;
	$pwd = getcwd();
	$logfile = "$pwd/$recipedir/$source/upload_log";
	system("rm -f $logfile &> /dev/null");
	chdir("$recipegit/trunk");
	log_system("git pull");
	if (!file_exists("$program")) {
		log_echo("This is a new program, creating directory...<br />");
		log_system("mkdir $program");
		log_system("git add $program");
		log_system("git commit -m 'Creating trunk program directory'");
		log_system("cp -R " .
			"$pwd/$recipedir/$source/$program/$version " .
			"$recipegit/trunk/$program");
		log_system("git add $program");
	} else {
		chdir("$program");
		list($originver, $origenrev) = explode('-', $origin);
		if ('none' == $originver) {
			log_echo("It seems this program has been added to the tree\n" .
			"since this version was submitted. It will not be possible\n" .
			"to commit this recipe. It must be submitted again based\n" .
			"on the entry in trunk.");
			return;
		}
		log_system("cp -Rf $pwd/$recipedir/$source/$program/$version .");
		log_system("git add $version");
	}
	
	chdir("$recipegit/revisions");
	log_system("git pull");
	if (!file_exists("$program")) {
		log_echo("Creating revisions/$program directory...<br />\n");
		log_system("mkdir $program");
		log_system("git add $program");
		log_system("git commit -m 'Creating revisions program directory'");
		$nextrev = 1;
	} else {
		chdir($program);
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
	log_echo("Committing trunk of $program $version...<br />");
	chdir("$recipegit/trunk/$program");
	
	$submitter = preg_replace('/[^-a-zA-Z0-9 _]/', '', $submitter);
	
	log_echo("Creating revision $program $version-r$nextrev...<br />");
	
	log_system("cp -a $version $recipegit/revisions/$program/$version-r$nextrev");
	chdir("$recipegit/revisions/$program");
	log_system("git add $version-r$nextrev");
	
	log_system("git commit -m ". 
		escapeshellarg("$program $version submitted by " . 
		"$submitter reviewed by " . username($_SESSION['email']) . 
		" in the recipe review panel")." --author ".
		escapeshellarg("$submitter <$submitteremail>"));

	log_system("git push");
	log_echo("Committed recipe to store.<br/>");
}

sajax_init();
sajax_export("refresh");
sajax_export("upload");
sajax_handle_client_request();

function get_status() {
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
	return $status;
}

if (authorised()) {
	$status = get_status();

	file_put_contents("$recipedir/$source/status", $status);
	file_put_contents("$recipedir/$source/reviewer", 
		$_SESSION['email']);
	file_put_contents("$recipedir/$source/message", 
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
		//upload();
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
	
	//header("Refresh: 10;url=$webroot");

	// ---------------------------------------------------
	echo "The recipe has been marked: $status <br/>";

	if ($status == 'approve') {
		?>

<h2 id="status">Uploading...</h2>
<pre id="log">
</pre>
<script language="JavaScript" type="text/javascript">

<?
sajax_show_javascript();
?>

var old_data = "";
var dots = 3;
var uploadDone = false;
var statusObj = document.getElementById("status");

function refresh_cb(new_data) {
	if (new_data != old_data) {
		document.getElementById("log").innerHTML = new_data;
		old_data = new_data;
	}
	if (!uploadDone) {
		statusObj.innerHTML = "Uploading" + (dots == 1 ? "." : (dots == 2 ? ".." : "..."));
		dots++;
		if (dots == 4) {
			dots = 1;
		}
		setTimeout('x_refresh("<?php echo $source; ?>", refresh_cb)', 1000);
	}
}

function upload_cb() {
	statusObj.innerHTML = "Uploaded!";
	uploadDone = true;
}
refresh_cb("");
x_upload("<?php echo $source; ?>", upload_cb);
</script>

		<?php
		if ($_POST['approve_and_close']) {
		?>
		<script type="text/javascript">
		setTimeout(function() {window.close();}, 2500);
		window.close();
		</script>
		<?php
		}

	}

} else
	echo "You are not authorised to approve or reject a recipe.";
