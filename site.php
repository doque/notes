<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">


	<head>
		<title>Notes</title>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js"></script>
		<link rel="stylesheet" type="text/css" href="css/ui-lightness/jquery-ui-1.8.8.custom.css"/>
		<link rel="stylesheet" type="text/css" href="css/notes.css"/>
		<link rel="icon" type="image/png" href="img/icon.png"/>
		<script type="text/javascript" src="js/notes.js"></script>
	</head>


	<body>

		<img id="spinner" src="img/spinner.gif"/>
		<div id="add">+</div>

		<div id="content">

			<? foreach ($notes AS $note) { ?>

				<div class="note ui-widget-content" noteID="<?=$note['id']?>" style="top: <?=$note['posY']?>; left: <?=$note['posX']?>;">
					<div class="noteContent">
						<div class="time"><?=$note['time']?></div>
						<div class="slider"></div>
						<!-- delete link -->
						<img class="delete" src="img/delete.png"/>
						<!-- textarea with note content, height/width/fontSize -->
						<textarea style="width: <?=$note['width']?>;
										 height: <?=$note['height']?>;
										 font-size: <?=$note['fontSize']?>;"><?=htmlentities($note['note'])?></textarea>
					</div>
				</div>

			<? } ?>

		</div>

	</body>

</html>