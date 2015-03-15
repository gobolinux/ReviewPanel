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

function getmeta($id) {
	global $webroot;
    $recipedir = $GLOBALS['recipedir'];
	if (false !== strpos($id, '..') || !strpos($id, '/'))
		exit();
	$meta = array();
	if (!file_exists("$recipedir/$id/submitter")) {
		$recipedir .= "/old";
		$meta['old'] = true;
	}
    $meta['location'] = "$recipedir/$id";
	$meta['submitter'] = file_get_contents("$recipedir/$id/submitter");
	$meta['submitteremail'] = file_get_contents("$recipedir/$id/submitteremail");
	$meta['program'] = file_get_contents("$recipedir/$id/program");
	$meta['version'] = file_get_contents("$recipedir/$id/version");
	$meta['status'] = file_get_contents("$recipedir/$id/status");
	$meta['modified'] = array_map('trim', file("$recipedir/$id/modified"));
	$meta['files'] = array_map('trim', file("$recipedir/$id/files"));
	if (file_exists("$recipedir/$id/reviewer"))
		$meta['reviewer'] = file_get_contents("$recipedir/$id/reviewer");
	if (file_exists("$recipedir/$id/origin"))
		$meta['origin'] = file_get_contents("$recipedir/$id/origin");
	if (file_exists("$recipedir/$id/message"))
		$meta['message'] = file_get_contents("$recipedir/$id/message");
	$meta['tarball'] = "$webroot/$recipedir/$id/{$meta['program']}--{$meta['version']}--recipe.tar.bz2";
	$meta['url'] = "$webroot/$id/{$meta['program']}--{$meta['version']}";
	list($meta['date'], $junk) = explode('-', $id);
	return $meta;
}

function sslash($str) {
	if(get_magic_quotes_gpc()) {
		return stripslashes($str);
	} else {
		return $str;
	}
}

function authorised() {
	return $_SESSION['authorised'] ? true : false;
}

function username($email) {
	return $GLOBALS['users'][$email];
}

function flash($type, $msg, $redir=null) {
	$_SESSION['flash'] = array($type, $msg);
	if ($redir) {
		ob_end_clean();
		header("Location: $redir");
		exit();
	}
}
