$.ajaxSetup({
	url: "index.php?ajax",
	type: "POST",
	dataType: "json",
});

$(document).ajaxStart(function() {
	$("#add").hide();
	$("#spinner").show();
}).ajaxStop(function() {
	$("#spinner").hide();
	$("#add").show();
});

$(function() {

	$(".note textarea").resizable(resizeOptions).change(function() {
		return changeListener(this);
	});

	$(".note").draggable(dragOptions);

	$(".slider").slider(slideOptions);

	$(".delete").live("click", function() {
		var parent = $(this).parents(".note");
			id = parent.attr("noteID");
		$.ajax({
			data: {
				"action": "deleteNote",
				"id": id
			},
			success: function(e) {
				if (e.success === true) {
					parent.fadeOut(300, function() {
						parent.remove();
					});
				}
			}
		});
	});

	$("#add").click(function() {
		// TODO: ajax call, save id, attach attributes
		$.ajax({
			data: {
				"action": "addNote"
			},
			success: function(e) {
				if (e.success === true) {
					var newNote = $(noteTemplate).appendTo("#content").effect("highlight", null, 800);
					newNote.draggable(dragOptions).find("textarea").resizable(resizeOptions).change(function() { return changeListener(this); });
					newNote.attr("noteID", e.noteID);
					newNote.find(".time").text(e.time);
					newNote.find(".slider").slider(slideOptions);
					newNote.find("textarea").focus();
				}
			}
		});
	});

	// TODO
	// slider

});

const noteTemplate = '<div class="note ui-widget-content">\
	<div class="noteContent">\
		<div class="time"></div>\
		<div class="slider"></div>\
		<img class="delete" src="css/delete.png"/>\
		<textarea></textarea>\
	</div>\
</div>';

const dragOptions = {
	opacity: .75,
	scroll: true,
	zIndex: 10,
	stop: function() {
		return changeListener($("textarea", this));
	}
};

const resizeOptions = {
	stop: function() {
		var parent = $(this).parents(".note"),
			self = $(this);
		var dimension = [self.css("width"), self.css("height")],
			position = [parent.css("top"), parent.css("left")],
			id = parent.attr("noteID"),
			note = $("textarea", self).val(),
			fontSize = $("textarea", self).css("font-size");
		$.ajax({
			data: {
				"action": "editNote",
				"posY": position[0],
				"posX": position[1],
				"width": dimension[0],
				"height": dimension[1],
				"fontSize": fontSize,
				"id": id,
				"note": note
			},
			success: function(e) {
			//	(console && console.log(e));
			}
		});
	}
};

const slideOptions = {
	min: 15,
	max: 48,
	step: 3,
	slide: function(event, ui) {
		$(this).parents(".note").find("textarea").css("font-size", ui.value+"px");
	},
	change: function(event, ui) {
		changeListener($(this).parents(".note").find("textarea"));
	},
	create: function(event, ui) {
		var fontSize = $(this).parents(".note").find("textarea").css("font-size").split("px")[0];
		$(this).slider("option", "value", fontSize);
	}
};

// TODO: specify div.note and textarea as parameter, DRY

var changeListener = function(element) {
	// div.note
	var parent = $(element).parents(".note"),
	// textarae
		self = $(element);
	var dimension = [self.css("width"), self.css("height")],
		position = [parent.css("top"), parent.css("left")],
		id = parent.attr("noteID"),
		note = $(element).val(),
		fontSize = self.css("font-size");
	$.ajax({
		data: {
			"action": "editNote",
			"posY": position[0],
			"posX": position[1],
			"width": dimension[0],
			"height": dimension[1],
			"fontSize": fontSize,
			"id": id,
			"note": note
		},
		success: function(e) {
		//	(console && console.log(e));
		}
	});
};