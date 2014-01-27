<?

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

error_reporting(E_ALL);


foreach (glob('inc/*.php') AS $file) require_once $file;
$db = new MySQL('host', 'user', 'pass', 'notes');


// ajax call
if (isset($_GET['ajax'])) {

	// needed because the $db class throws an exception on error
	try {

		// see which action has been called
		switch (trim($_POST['action'])) {

			// adding new note with no contents, only time
			// returns the id of added note
			case 'addNote':
					$time = time();
					$db->query('INSERT INTO `notes` SET `id` = NULL, `time` = "%s"', date('Y-m-d H:i:s', $time));
					$output = array('success' => true, 'noteID' => $db->insertID(), 'time' => date('d.m.Y (H:i:s)', $time));
				break;

			// editing existing note
			// needs fields $_POST['note'] and $_POST['id']
			// returns id of edited note
			case 'editNote':
					$note = utf8_decode($_POST['note']);
					$db->query('UPDATE `notes` SET `note` = "%s",
													`posX` = "%s",
													`posY` = "%s",
													`width` = "%s",
													`height` = "%s",
													`fontSize` ="%s"
								WHERE id = %d', $note, $_POST['posX'], $_POST['posY'], $_POST['width'], $_POST['height'], $_POST['fontSize'], $_POST['id']);
					$output = array('success' => true, 'noteID' => $_POST['id']);
				break;

			// delete existing note
			// returns only {"success": true}
			case 'deleteNote':
					$db->query('UPDATE `notes` SET `deleted` = 1 WHERE `id` = %d', $_POST['id']);
					$output = array('success' => true);
				break;

			// invalid action defined
			default:
				$output = array('success' => false, 'reason' => 'invalid action defined');

		}

	// in case of error, the error information is transported
	// through ajax
	} catch (MySQLException $e) {
		$output = array('success' => false, 'reason' => $e->__toString());
	}

	// output to client
	exit(json_encode($output));

// no ajax requests were made, just include the website with textares
} else {
	// read all notes into an array
	// %%d is used for literal %d, see http://de2.php.net/manual/en/function.sprintf.php
	$db->query('SELECT `id`,
						DATE_FORMAT(`time`, "%%d.%%m.%%Y (%%H:%%i:%%s)") AS `time`,
						`note`,
						`posX`,
						`posY`,
						`width`,
						`height`,
						`fontSize`
				FROM `notes`
				WHERE `deleted` = 0
				');
	$notes = array();
	while ($note = $db->fetchAssoc()) {
		$notes[] = $note;
	}

	include 'site.php';

}


?>