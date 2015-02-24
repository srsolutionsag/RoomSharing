<#1>
<?php
// This script creates the necessary tables for the "Roomsharing System" plugin
// Version 0.2
// author: T.Wolscht, T. Matern, T. Röhrig
// ##########################
// 'rep_robj_xrs_rattr'
// ##########################
$table_name = 'rep_robj_xrs_rattr';
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'name' => array(
		'type' => 'text',
		'length' => 45,
		'notnull' => true
	),
	'pool_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($table_name))
{
	$ilDB->dropTable($table_name);
}
$ilDB->createTable($table_name, $fields);
// add primary key
$ilDB->addPrimaryKey($table_name, array("id"));
// add sequence
$ilDB->createSequence($table_name);
// add index
$ilDB->addIndex($table_name, array('pool_id'), 'i1');

// ##########################
// 'rep_robj_xrs_bookings'
// ##########################
$table_name = "rep_robj_xrs_bookings";
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'date_from' => array(
		'type' => 'timestamp',
		'notnull' => true
	),
	'date_to' => array(
		'type' => 'timestamp',
		'notnull' => true
	),
	'seq_id' => array(
		'type' => 'integer',
		'length' => 4,
		'default' => null
	),
	'room_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'pool_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'user_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'subject' => array(
		'type' => 'text',
		'length' => 255
	)
);
//delete Table, if exists
if ($ilDB->tableExists($table_name))
{
	$ilDB->dropTable($table_name);
}
$ilDB->createTable($table_name, $fields);
// add primary key
$ilDB->addPrimaryKey($table_name, array("id"));
// add sequence
$ilDB->createSequence($table_name);
// add index
$ilDB->addIndex($table_name, array('seq_id'), 'i1');
$ilDB->addIndex($table_name, array('room_id'), 'i2');
$ilDB->addIndex($table_name, array('pool_id'), 'i3');
$ilDB->addIndex($table_name, array('user_id'), 'i4');

// ##########################
// 'rep_robj_xrs_book_seqe'
// ##########################
$table_name = "rep_robj_xrs_book_seqe";
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'type' => array(
		'type' => 'text',
		'length' => 45
	),
	'pool_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($table_name))
{
	$ilDB->dropTable($table_name);
}
$ilDB->createTable($table_name, $fields);
// add primary key
$ilDB->addPrimaryKey($table_name, array("id"));
// add sequence
$ilDB->createSequence($table_name);
// add index
$ilDB->addIndex($table_name, array('pool_id'), 'i1');

// ##########################
// 'rep_robj_xrs_book_user'
// ##########################
$table_name = "rep_robj_xrs_book_user";
$fields = array(
	'booking_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'user_id' => array(
		'type' => 'integer',
		'length' => 4
	)
);
//delete Table, if exists
if ($ilDB->tableExists($table_name))
{
	$ilDB->dropTable($table_name);
}
$ilDB->createTable($table_name, $fields);
// add primary key
$ilDB->addPrimaryKey($table_name, array("booking_id", "user_id"));

// ##########################
// 'rep_robj_xrs_buildings'
// ##########################
$table_name = "rep_robj_xrs_buildings";
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'name' => array(
		'type' => 'text',
		'length' => 45
	),
	'pool_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($table_name))
{
	$ilDB->dropTable($table_name);
}
$ilDB->createTable($table_name, $fields);
// add primary key
$ilDB->addPrimaryKey($table_name, array("id"));
// add sequence
$ilDB->createSequence($table_name);
// add index
$ilDB->addIndex($table_name, array('pool_id'), 'i1');

// ##########################
// 'rep_robj_xrs_pools'
// ##########################
$table_name = "rep_robj_xrs_pools";
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'short_description' => array(
		'type' => 'text',
		'length' => 1000
	),
	'pool_online' => array(
		'type' => 'integer',
		'length' => 1,
		'default' => 0
	)
);
//delete Table, if exists
if ($ilDB->tableExists($table_name))
{
	$ilDB->dropTable($table_name);
}
$ilDB->createTable($table_name, $fields);
// add primary key
$ilDB->addPrimaryKey($table_name, array("id"));
// add sequence
$ilDB->createSequence($table_name);

// ##########################
// 'rep_robj_xrs_rooms'
// ##########################
$table_name = "rep_robj_xrs_rooms";
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'name' => array(
		'type' => 'text',
		'length' => 45,
		'notnull' => true
	),
	'type' => array(
		'type' => 'text',
		'length' => 45
	),
	'min_alloc' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true,
		'default' => 1
	),
	'max_alloc' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'file_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'building_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'pool_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($table_name))
{
	$ilDB->dropTable($table_name);
}
$ilDB->createTable($table_name, $fields);
// add primary key
$ilDB->addPrimaryKey($table_name, array("id"));
// add sequence
$ilDB->createSequence($table_name);
// add index
$ilDB->addIndex($table_name, array('max_alloc'), 'i1');
$ilDB->addIndex($table_name, array('pool_id'), 'i2');

// ##########################
// 'rep_robj_xrs_room_attr'
// ##########################
$table_name = "rep_robj_xrs_room_attr";
$fields = array(
	'room_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'att_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'count' => array(
		'type' => 'integer',
		'length' => 4
	)
);
//delete Table, if exists
if ($ilDB->tableExists($table_name))
{
	$ilDB->dropTable($table_name);
}
$ilDB->createTable($table_name, $fields);
// add primary key
$ilDB->addPrimaryKey($table_name, array("room_id", "att_id"));
?>

<#2>
<?php
// Add tables for variable attributes for bookings
// Author: R. Heimsoth
// ##########################
// 'rep_robj_xrs_battr'
// ##########################
$table_name = 'rep_robj_xrs_battr';
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'name' => array(
		'type' => 'text',
		'length' => 45,
		'notnull' => true
	),
	'pool_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($table_name))
{
	$ilDB->dropTable($table_name);
}
$ilDB->createTable($table_name, $fields);
// add primary key
$ilDB->addPrimaryKey($table_name, array("id"));
// add sequence
$ilDB->createSequence($table_name);
// add index
$ilDB->addIndex($table_name, array('pool_id'), 'i1');

// ##########################
// 'rep_robj_xrs_book_attr'
// ##########################
$table_name = "rep_robj_xrs_book_attr";
$fields = array(
	'booking_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'attr_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'value' => array(
		'type' => 'text',
		'length' => 250
	)
);
//delete Table, if exists
if ($ilDB->tableExists($table_name))
{
	$ilDB->dropTable($table_name);
}
$ilDB->createTable($table_name, $fields);
// add primary key
$ilDB->addPrimaryKey($table_name, array("booking_id"));
?>

<#3>
<?php
// Add tables for floor plans
// Author: T. Matern, T. Röhrig, T. Wolscht
// ##########################
// 'rep_robj_xrs_fplans'
// ##########################
$table_name = 'rep_robj_xrs_fplans';
$fields = array(
	'file_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'pool_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($table_name))
{
	$ilDB->dropTable($table_name);
}
$ilDB->createTable($table_name, $fields);
// add primary key
$ilDB->addPrimaryKey($table_name, array("file_id"));
// add sequence
//$ilDB->createSequence($table_name);
// add index
$ilDB->addIndex($table_name, array('file_id'), 'i1');
?>

<#4>
<?php
// Mit diesem Skript wird ein Grunddatenstand für das "Roomsharing System" eingefügt.
// Version 0.1
// author: B.Hitzelberger
// Delete Anweisung um alle Tabellen zu leeren
// Removed: Testdata
?>

<#5>
<?php
$ilDB->dropPrimaryKey("rep_robj_xrs_book_attr");
$ilDB->addPrimaryKey("rep_robj_xrs_book_attr", array('booking_id', 'attr_id'));
?>
<#6>
<?php
//Testattributes for Roomsharing Bookings
$resultNextId = $ilDB->nextId("rep_robj_xrs_battr");
$ilDB->manipulate("INSERT INTO rep_robj_xrs_battr (id, name, pool_id) VALUES "
	. "(" . $ilDB->quote($resultNextId, 'integer') . ", 'Modul', 1)");
$resultNextId = $ilDB->nextId("rep_robj_xrs_battr");
$ilDB->manipulate("INSERT INTO rep_robj_xrs_battr (id, name, pool_id) VALUES "
	. "(" . $ilDB->quote($resultNextId, 'integer') . ", 'Kurs', 1)");
$resultNextId = $ilDB->nextId("rep_robj_xrs_battr");
$ilDB->manipulate("INSERT INTO rep_robj_xrs_battr (id, name, pool_id) VALUES "
	. "(" . $ilDB->quote($resultNextId, 'integer') . ", 'Semester', 1)");
?>

<#7>
<?php
// Additional main settings: max booking time and room use aggreement.
/* @var $ilDB ilDB */
$table = 'rep_robj_xrs_pools';

$agreementColumn = 'rooms_agreement';
$agreementAttributes = array(
	'type' => 'integer',
	"length" => 4,
	"default" => "0",
	'notnull' => true);
$ilDB->addTableColumn($table, $agreementColumn, $agreementAttributes);

$bookTimeColumn = 'max_book_time';
$bookTimeAttributes = array(
	'type' => 'timestamp',
	"default" => "1970-01-01 03:00:00.000000",
	'notnull' => true);
$ilDB->addTableColumn($table, $bookTimeColumn, $bookTimeAttributes);
?>

<#8>
<?php
// Additional main setting: calendar-id to create one calender per poolId.
// Additional attribute in bookings: public to clarify if username is visible (used later).
/* @var $ilDB ilDB */
$tablePools = 'rep_robj_xrs_pools';

$calendarColumn = 'calendar_id';
$calendarAttributes = array(
	'type' => 'integer',
	"length" => 4,
	"default" => "0",
	'notnull' => true);
$ilDB->addTableColumn($tablePools, $calendarColumn, $calendarAttributes);

$tableBookings = 'rep_robj_xrs_bookings';
$bookPublicColumn = 'public_booking';
$bookPublicAttributes = array(
	'type' => 'boolean');
$ilDB->addTableColumn($tableBookings, $bookPublicColumn, $bookPublicAttributes);
?>

<#9>
<?php
// Add comment to booking table.
/* @var $ilDB ilDB */
$tableBookings = 'rep_robj_xrs_bookings';
$commentColumn = 'bookingcomment';

$commentAttributes = array(
	'type' => 'text',
	'length' => 4000);
$ilDB->addTableColumn($tableBookings, $commentColumn, $commentAttributes);
?>

<#10>
<?php
// Set building_ids of rooms to 0.
$ilDB->manipulate("UPDATE rep_robj_xrs_rooms SET building_id = 0");
?>

<#11>
<?php
//Additional of local group assignment
// Author: R. Heimsoth
// ##########################
// 'rep_robj_xrs_groups'
// ##########################
$tableGroups = 'rep_robj_xrs_groups';

$fieldsGroups = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'name' => array(
		'type' => 'text',
		'length' => 75,
		'notnull' => true
	),
	'description' => array(
		'type' => 'text',
		'length' => 1000
	),
	'role_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'pool_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($tableGroups))
{
	$ilDB->dropTable($tableGroups);
}
$ilDB->createTable($tableGroups, $fieldsGroups);
// add primary key
$ilDB->addPrimaryKey($tableGroups, array("id"));
// add sequence
$ilDB->createSequence($tableGroups);
// add index
$ilDB->addIndex($tableGroups, array('pool_id'), 'i1');

// Author: R. Heimsoth
// ##########################
// 'rep_robj_xrs_grp_user'
// ##########################
$tableGroupUser = "rep_robj_xrs_grp_user";
$fieldsGroupUser = array(
	'group_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'user_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($tableGroupUser))
{
	$ilDB->dropTable($tableGroupUser);
}
$ilDB->createTable($tableGroupUser, $fieldsGroupUser);
// add primary key
$ilDB->addPrimaryKey($tableGroupUser, array("group_id"));
?>

<#12>
<?php
//Additional of local group to right assignment
// Author: R. Heimsoth
// ##########################
// 'rep_robj_xrs_groups'
// 'rep_robj_xrs_grp_priv'
// ##########################
//Add lock attribute for group

$tableGroups = 'rep_robj_xrs_groups';

$fieldsGroups = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'name' => array(
		'type' => 'text',
		'length' => 75,
		'notnull' => true
	),
	'description' => array(
		'type' => 'text',
		'length' => 1000
	),
	'role_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'locked' => array(
		'type' => 'integer',
		'length' => 1,
		'default' => 0
	),
	'pool_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($tableGroups))
{
	$ilDB->dropTable($tableGroups);
}
$ilDB->createTable($tableGroups, $fieldsGroups);
// add primary key
$ilDB->addPrimaryKey($tableGroups, array("id"));
// add sequence
$ilDB->createSequence($tableGroups);
// add index
$ilDB->addIndex($tableGroups, array('pool_id'), 'i1');

$tableGroupPriv = 'rep_robj_xrs_grp_priv';

$fieldsGroupPriv = array(
	'group_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'accessappointments' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accesssearch' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addownbookings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addparticipants' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addsequencebookings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'cancelbookinglowerpriority' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'seebookingsofrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'deleterooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'deletefloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accesssettings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addgroup' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'lockprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0)
);
//delete Table, if exists
if ($ilDB->tableExists($tableGroupPriv))
{
	$ilDB->dropTable($tableGroupPriv);
}
$ilDB->createTable($tableGroupPriv, $fieldsGroupPriv);
// add primary key
$ilDB->addPrimaryKey($tableGroupPriv, array("group_id"));
?>

<#13>
<?php
// Author: R. Heimsoth
// Drop Primary Key
// ##########################
// 'rep_robj_xrs_grp_user'
// ##########################

$tableGroupUser = "rep_robj_xrs_grp_user";
$ilDB->dropPrimaryKey($tableGroupUser);
?>

<#14>
<?php
// Author: R. Heimsoth
// Rename groups to classes
// ##########################
// 'rep_robj_xrs_classes'
// 'rep_robj_xrs_cls_user'
// 'rep_robj_xrs_cls_priv'
// ##########################

$tableClasses = 'rep_robj_xrs_classes';

$fieldsClasses = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'name' => array(
		'type' => 'text',
		'length' => 75,
		'notnull' => true
	),
	'description' => array(
		'type' => 'text',
		'length' => 1000
	),
	'role_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'pool_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($tableClasses))
{
	$ilDB->dropTable($tableClasses);
}
$ilDB->createTable($tableClasses, $fieldsClasses);
// add primary key
$ilDB->addPrimaryKey($tableClasses, array("id"));
// add sequence
$ilDB->createSequence($tableClasses);
// add index
$ilDB->addIndex($tableClasses, array('pool_id'), 'i1');

$tableClassUser = "rep_robj_xrs_cls_user";
$fieldsClassUser = array(
	'class_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'user_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($tableClassUser))
{
	$ilDB->dropTable($tableClassUser);
}
$ilDB->createTable($tableClassUser, $fieldsClassUser);

$tableClassPriv = 'rep_robj_xrs_cls_priv';

$fieldsClassPriv = array(
	'class_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'accessappointments' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accesssearch' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addownbookings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addparticipants' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addsequencebookings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'cancelbookinglowerpriority' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'seebookingsofrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'deleterooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'deletefloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accesssettings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addclass' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editclass' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'deleteclass' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'lockprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0)
);
//delete Table, if exists
if ($ilDB->tableExists($tableClassPriv))
{
	$ilDB->dropTable($tableClassPriv);
}
$ilDB->createTable($tableClassPriv, $fieldsClassPriv);

// add primary key
$ilDB->addPrimaryKey($tableClassPriv, array("class_id"));
?>

<#15>
<?php
// Author: R. Heimsoth
// Delete old group tables
// ##########################
// 'rep_robj_xrs_groups'
// 'rep_robj_xrs_grp_user'
// 'rep_robj_xrs_grp_priv'
// ##########################
$tableGroups = "rep_robj_xrs_groups";
$tableGroupUser = "rep_robj_xrs_grp_user";
$tableGroupPriv = "rep_robj_xrs_grp_priv";
if ($ilDB->tableExists($tableGroupPriv))
{
	$ilDB->dropTable($tableGroupPriv);
}
if ($ilDB->tableExists($tableGroupUser))
{
	$ilDB->dropTable($tableGroupUser);
}
if ($ilDB->tableExists($tableGroups))
{
	$ilDB->dropTable($tableGroups);
}
?>

<#16>
<?php
// Author: R. Heimsoth
// Add locked
// ##########################
// 'rep_robj_xrs_groups'
// ##########################
$tableClasses = 'rep_robj_xrs_classes';

$fieldsClasses = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'name' => array(
		'type' => 'text',
		'length' => 75,
		'notnull' => true
	),
	'description' => array(
		'type' => 'text',
		'length' => 1000
	),
	'role_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'locked' => array(
		'type' => 'integer',
		'length' => 1,
		'default' => 0
	),
	'pool_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($tableClasses))
{
	$ilDB->dropTable($tableClasses);
}
$ilDB->createTable($tableClasses, $fieldsClasses);
// add primary key
$ilDB->addPrimaryKey($tableClasses, array("id"));
// add sequence
$ilDB->createSequence($tableClasses);
// add index
$ilDB->addIndex($tableClasses, array('pool_id'), 'i1');
?>

<#17>
<?php
// Author: R. Heimsoth
// Add new privilege "addUnlimitedBookings", "notificationSetting",
// "adminRoomAttributes", "adminBookingAttributes"
// ##########################
// 'rep_robj_xrs_grp_priv'
// ##########################
$tableClassPriv = 'rep_robj_xrs_cls_priv';

$fieldsClassPriv = array(
	'class_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'accessappointments' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accesssearch' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addownbookings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addparticipants' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addsequencebookings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addunlimitedbookings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'notificationsettings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'adminbookingattributes' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'cancelbookinglowerpriority' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'seebookingsofrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'deleterooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'adminroomattributes' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'deletefloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accesssettings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addclass' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editclass' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'deleteclass' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'lockprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0)
);
//delete Table, if exists
if ($ilDB->tableExists($tableClassPriv))
{
	$ilDB->dropTable($tableClassPriv);
}
$ilDB->createTable($tableClassPriv, $fieldsClassPriv);

// add primary key
$ilDB->addPrimaryKey($tableClassPriv, array("class_id"));
?>

<#18>
<?php
// Author: R. Heimsoth
// Add priority
// ##########################
// 'rep_robj_xrs_groups'
// ##########################
$tableClasses = 'rep_robj_xrs_classes';

$fieldsClasses = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'name' => array(
		'type' => 'text',
		'length' => 75,
		'notnull' => true
	),
	'description' => array(
		'type' => 'text',
		'length' => 1000
	),
	'priority' => array(
		'type' => 'integer',
		'length' => 2,
		'default' => 0
	),
	'role_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'locked' => array(
		'type' => 'integer',
		'length' => 1,
		'default' => 0
	),
	'pool_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($tableClasses))
{
	$ilDB->dropTable($tableClasses);
}
$ilDB->createTable($tableClasses, $fieldsClasses);
// add primary key
$ilDB->addPrimaryKey($tableClasses, array("id"));
// add sequence
$ilDB->createSequence($tableClasses);
// add index
$ilDB->addIndex($tableClasses, array('pool_id'), 'i1');
?>

<#19>
<?php
// Author: R. Heimsoth
// Add new privilege "seeNonPublicBookingInformation"
// ##########################
// 'rep_robj_xrs_grp_priv'
// ##########################
$tableClassPriv = 'rep_robj_xrs_cls_priv';

$fieldsClassPriv = array(
	'class_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'accessappointments' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accesssearch' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addownbookings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addparticipants' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addsequencebookings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addunlimitedbookings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'seenonpublicbookinginformation' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'notificationsettings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'adminbookingattributes' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'cancelbookinglowerpriority' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'seebookingsofrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'deleterooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'adminroomattributes' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'deletefloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accesssettings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addclass' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editclass' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'deleteclass' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'lockprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0)
);
//delete Table, if exists
if ($ilDB->tableExists($tableClassPriv))
{
	$ilDB->dropTable($tableClassPriv);
}
$ilDB->createTable($tableClassPriv, $fieldsClassPriv);

// add primary key
$ilDB->addPrimaryKey($tableClassPriv, array("class_id"));
?>

<#20>
<?php
// Author: R. Heimsoth
// Remove classes
// ##########################
// 'rep_robj_xrs_classes'
// ##########################
$tableClasses = 'rep_robj_xrs_classes';

$fieldsClasses = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'name' => array(
		'type' => 'text',
		'length' => 75,
		'notnull' => true
	),
	'description' => array(
		'type' => 'text',
		'length' => 1000
	),
	'priority' => array(
		'type' => 'integer',
		'length' => 2,
		'default' => 0
	),
	'role_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'locked' => array(
		'type' => 'integer',
		'length' => 1,
		'default' => 0
	),
	'pool_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($tableClasses))
{
	$ilDB->dropTable($tableClasses);
}
$ilDB->createTable($tableClasses, $fieldsClasses);
// add primary key
$ilDB->addPrimaryKey($tableClasses, array("id"));
// add sequence
$ilDB->createSequence($tableClasses);
// add index
$ilDB->addIndex($tableClasses, array('pool_id'), 'i1');

//Remove assigned users
$tableClassUser = "rep_robj_xrs_cls_user";
$fieldsClassUser = array(
	'class_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'user_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);
//delete Table, if exists
if ($ilDB->tableExists($tableClassUser))
{
	$ilDB->dropTable($tableClassUser);
}
$ilDB->createTable($tableClassUser, $fieldsClassUser);
?>

<#21>
<?php
// Add calendar_id to booking table.
/* @var $ilDB ilDB */
$tableBookings = 'rep_robj_xrs_bookings';
$calendarIdColumn = 'calendar_entry_id';

$calendarIdAttributes = array(
	'type' => 'integer',
	'length' => 4);
//Drop column, if exists
if ($ilDB->tableColumnExists($tableBookings, $calendarIdColumn))
{
	$ilDB->dropTableColumn($tableBookings, $calendarIdColumn);
}
$ilDB->addTableColumn($tableBookings, $calendarIdColumn, $calendarIdAttributes);
?>


<#22>
<?php
// Author: R. Heimsoth
// Add new privilege "accessImport"
// ##########################
// 'rep_robj_xrs_grp_priv'
// ##########################
$tableClassPriv = 'rep_robj_xrs_cls_priv';

$fieldsClassPriv = array(
	'class_id' => array(
		'type' => 'integer',
		'length' => 4
	),
	'accessappointments' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accesssearch' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addownbookings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addparticipants' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addsequencebookings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addunlimitedbookings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'seenonpublicbookinginformation' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'notificationsettings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'adminbookingattributes' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'cancelbookinglowerpriority' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'seebookingsofrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editrooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'deleterooms' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'adminroomattributes' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editfloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'deletefloorplans' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accesssettings' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'addclass' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editclass' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'deleteclass' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'editprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'lockprivileges' => array('type' => 'integer', 'length' => 1, 'default' => 0),
	'accessimport' => array('type' => 'integer', 'length' => 1, 'default' => 0)
);
//delete Table, if exists
if ($ilDB->tableExists($tableClassPriv))
{
	$ilDB->dropTable($tableClassPriv);
}
$ilDB->createTable($tableClassPriv, $fieldsClassPriv);

// add primary key
$ilDB->addPrimaryKey($tableClassPriv, array("class_id"));
?>