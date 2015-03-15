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

function dateformat($timestamp) {
	$timezone = date("O", $timestamp);
	return date("Y-m-d", $timestamp) . 'T' . date("H:i:s", $timestamp) . substr($timezone,
 0, 3) . ':' . substr($tz, 3, 2);
}

$pendings = array();
$d = dir($recipedir);
$skipemails = array();
if ($_GET['skipemails'])
	$skipemails = $_GET['skipemails'];
while (false !== ($entry = $d->read())) {
	if (substr($entry, 0, 1) == '.') continue;
	$s = dir("$recipedir/$entry");
	while (false !== ($subdir = $s->read())) {
		if (substr($subdir, 0, 1) == '.') continue;
		extract(getmeta("$entry/$subdir"));
		list($time, $junk) = explode('-', $entry);
		$tuple = array($program, $version, $submitter, "$entry/$subdir", $reviewer);
		if ('pending' == $status &&
		    !in_array($submitteremail, $skipemails))
			$pendings[$time] = $tuple;
	}
}

krsort($pendings);

$baseurl = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $webroot;
ob_end_clean();
header('Content-type: application/atom+xml');
echo '<?xml version="1.0" encoding="utf-8"?>'
?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title><?php echo $basetitle?> - Pending</title>
  <link rel="alternate" type="text/html" href="<?php echo $baseurl?>/<?php
  if (count($skipemails)) {
    echo "?";
    foreach ($skipemails as $se)
      echo "skipemails[]=$se&amp;";
  }
  ?>"/>
  <link rel="self" type="application/atom+xml" href="<?php echo $webroot?>/atom"/>
  <id><?php echo $webroot?>/atom</id>
  <updated>2007-12-21T04:54:09-08:00</updated>
<?php foreach ($pendings as $time=>$it) {?>
  <entry>
    <title><?php echo htmlspecialchars("$it[0] $it[1] from $it[2]")?></title>
    <link rel="alternate" type="text/html" href="<?php echo $baseurl?>/<?php echo $it[3]?>/<?php echo $it[0]?>--<?php echo $it[1]?>" />
    <id><?php echo $webroot?>/<?php echo $it[3]?></id>
    <published><?php echo dateformat($time);?></published>
    <updated><?php echo dateformat($time);?></updated>
    <author>
      <name><?php echo htmlspecialchars($it[2])?></name>
    </author>
    <content type="html"><![CDATA[<?php echo nl2br(htmlspecialchars(file_get_contents("$recipedir/$it[3]/changes.patch")));?>
    ]]></content>
  </entry>
<?php }?>
</feed>
<?php
exit();
