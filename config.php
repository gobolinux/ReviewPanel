<?php
$recipedir = "files";
$recipesvn = "/home/gobo_recipes/recipes.gobolinux.org/reviewpanel/svn";
$recipesvnserv = "http://svn.gobolinux.org/recipes";

$host = "recipes.gobolinux.org";
$webroot = "/review";
$basetitle = "Recipe Review Panel";
$deletedcolour = '#f00';
$newcolour = '#0f0';
$replacedcolour = '#d00';
$replacementcolour = '#0c0';
$replacedcolour = 'white';
$replacementcolour = 'white';

# Password is hidden here, that isn't exported.
# It defines $SVN_USER and $SVN_PASS
require 'svn/credentials.php';

$users = array(
 'michael@gobolinux.org' => 'Michael Homer',
 'jonka750@student.liu.se' => 'Jonas Karlsson',
 'jonas@gobolinux.org' => 'Jonas Karlsson',
 'hisham.hm@gmail.com' => 'Hisham Muhammad',
 'hisham@apple2.com' => 'Hisham Muhammad',
 'hisham@gobolinux.org' => 'Hisham Muhammad',
 'lucasvr@gobolinux.org' => 'Lucas C. Villa Real',
 'carlo@calica.com' => 'Carlo Calica',
 'detsch@gobolinux.org' => 'André Detsch',
 'gobo.users@gmail.com' => 'Daniele Maccari',
 'giamby@infinito.it' => 'Giambattista Bloisi',
 'aitor.ituri@gmx.com' => 'Aitor Pérez Iturri',
 'namegduf@fudgeman.org' => 'John Robert Beshir',
);

$user_domains = array(
 'gobolinux.org',
);
