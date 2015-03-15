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
require 'lib.php';

ob_start();
session_set_cookie_params(31536000);
session_start();
if ($_REQUEST['mode'] == 'auth')
	require 'auth.php';
$flash = $_SESSION['flash'];
$_SESSION['flash'] = array();
session_write_close();
if ($_REQUEST['mode'] == 'auth')
	print ""; # do nothing
elseif ($_REQUEST['mode'] == 'submit')
	require 'submit.php';
elseif ($_REQUEST['mode'] == 'approve')
	require 'approve.php';
elseif ($_REQUEST['mode'] == 'diff')
	require 'diff.php';
elseif ($_REQUEST['mode'] == 'edit')
	require 'edit.php';
elseif ($_REQUEST['mode'] == 'delete')
	require 'delete.php';
elseif ($_REQUEST['mode'] == 'atom')
    require 'atom.php';
elseif ($_REQUEST['mode'] == 'housekeeping')
    require 'housekeeping.php';
elseif ($_REQUEST['mode'] == 'getsource')
	require 'getsource.php';
else {
	require 'main.php';
}
$content = ob_get_contents();
ob_end_clean();
header("Content-type: text/html; charset=utf-8");
?>
<html>
 <head>
  <title><?php echo ($title ? "$title - " : '')?><?php echo $basetitle?></title>
  <style type="text/css">
   html, body { margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; }
   #footer { font-size: 80% ; }
   .flash.good { background: #cfc; color: black; border: 1px solid #080; }
   .flash.bad { background: #fcc; color: black; border: 1px solid #800; }
   .warning {
     background: #ffc url(/icons/error.png) no-repeat;
     color: black;
     border: 1px solid #880;
     margin: 1em 0 1em 0;
     padding: 1px 1px 1px 20px;
     font-weight: bold;
   }
   ins, .new {
     background: #0e0;
     text-decoration: none;
     color: inherit;
   }
   del, .deleted {
     background: #e00;
     text-decoration: none;
     color: white;
   }
   tr:nth-child(even) {
     background: #ddd;
   }
   td:nth-child(0n+2) {
     border-left: 1px solid #888;
   }
  </style>
  <link rel="stylesheet" type="text/css" href="http://gobolinux.org/2006.css"/>
  <link rel="alternate" type="application/atom+xml" title="Atom" href="<?php echo $webroot?>/atom" />
 </head>
 <body>
  <h1><?php echo ($title ? "$title - " : '')?><a href="<?php echo $webroot?>/"><?php echo $basetitle?></a></h1>
<?php
if (@is_array($flash) && count($flash)) {
	echo "<div class=\"flash {$flash[0]}\">{$flash[1]}</div>";
}
?>
  <div id="login">
<?php
if ($_SESSION['email'])
	echo "Logged in as ". username($_SESSION['email']) . " &lt;{$_SESSION['email']}&gt;.".
		" (<a href=\"$webroot/auth/logout\">Log out</a>)";
else
	echo "<a href=\"$webroot/auth/login\">Log in if you are a recipe reviewer</a>.";
?>
  </div>
<?php echo $content?>
  <div id="footer">This software is available under the GNU Affero General Public Licence version 3 or later. <a href="<?php echo $webroot?>/getsource">Get the source code</a>.</div>
 </body>
</html>
