<?php
/* 
Part of: UKM Norge core
Description: SQL-klasse for bruk av SQL-sp¿rringer opp mot UKM-databasen.
Author: UKM Norge / M Mandal
Maintainer: UKM Norge / A Hustad & M Mandal
Version: 4.0 
Comments: Now utilizes mysqli instead of mysql
*/

require_once('UKM/Database/SQL/Query.php');
require_once('UKM/Database/SQL/Insert.php');
require_once('UKM/Database/SQL/Delete.php');
require_once('UKM/Database/SQL/Write.php');

class SQL extends UKMNorge\Database\SQL\Query {};
class SQLins extends UKMNorge\Database\SQL\Insert {};
class SQLdel extends UKMNorge\Database\SQL\Delete {};
class SQLwrite extends UKMNorge\Database\SQL\Write{};
?>