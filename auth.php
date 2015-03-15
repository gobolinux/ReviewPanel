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
if ($_REQUEST['action']=='authenticate') {
	$email = $_REQUEST['email'];
	$token = $_REQUEST['token'];
	if ($token == md5($email . getmyinode())) {
		$_session['authorised'] = false;
		list($username, $domain) = explode('@', $email);
		if (array_key_exists($email, $users))
			$_SESSION['authorised'] = true;
		if (array_key_exists($domain, $user_domains))
			$_SESSION['authorised'] = true;
		$_SESSION['email'] = $email;
		flash('good', 'Logged in successfully', "$webroot/");
	} else {
		flash('bad', "Login failed. Authentication token mismatch.", "$webroot");
	}
} elseif ($_REQUEST['action'] == 'logout') {
	$_SESSION['authorised'] = false;
	$_SESSION['email'] = '';
	flash('good', 'Logged out successfully', "$webroot/");
} elseif ($_REQUEST['action'] == 'login') {
?>
 <form action="<?php echo $webroot?>/auth/dologin" method="post">
  <label for="email">Email address:</label> <input id="email" name="email" type="text" />
  <input type="submit" value="Send login token" />
 </form>
<?php
} elseif ($_REQUEST['action'] == 'dologin') {
	#if (array_key_exists($_REQUEST['email'], $users)) {
		$token = md5($_REQUEST['email'] . getmyinode());
		$msg = "You, or someone claiming to be you, requested a login token to the recipe review panel. You can log in using this URL: http://{$_SERVER['HTTP_HOST']}{$webroot}/auth/authenticate?token=$token&email={$_REQUEST['email']}\nIf you did not request this token, you may ignore this message.";
		mail($_REQUEST['email'], 'Recipe Review login', $msg, "From: noreply@{$_SERVER['HTTP_HOST']}\r\n");
		echo "A login token has been sent to this address.";
	#} else {
	#	flash('bad', "This email address is not authorised to log in.", "$webroot/auth/login");
	#}
}
