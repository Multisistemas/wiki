// Dokuwiki table editing plugin
// Author: Bohumir Zamecnik <bohumir|zamecnik|org>
// Last modified: 22.11.2008
// License: GNU GPL 2
// Website: http://zamecnik.org/projekty/dokuwiki_tableedit
//
// Purpose:
// The goal of this plugin is to simplify handling Dokuwiki table columns.
// It's not easy to move some columns around, add them or remove them by hand.
// This plugin will offer a friendly user iterface to underlying manipulation
// with the table markup using regular expression.

//function replaceColumns(inputText, replaceSpec, columnChar, defaultContent) {
function replaceColumns(inputText, replaceSpec) {
	var headChar = "^";
	var headDefault = "";
	var normalChar = "|";
	var normalDefault = " ";
	var nColumns = countColumns(inputText);
	
	for (var i = 0; i < replaceSpec.length; i++) {
		if ((replaceSpec[i] <= 0) || (replaceSpec[i] > nColumns)) {
			replaceSpec[i] = "&"; // will be replaced by a default char
		}
		replaceSpec[i] = "$" + replaceSpec[i];
	}
	
	var pattern = "^" + multiple("#", nColumns)
		.join("([^#]*)") + "[ \t]*$";
	
	var replacePattern = replaceSpec.join("#");
	if (replaceSpec.length > 0) {
		replacePattern = "#" + replacePattern + "#";
	}
		
	var currentPattern = new RegExp();
	
	currentPattern.compile(pattern.replace(/#/g, "\\"+headChar), "gm");
	currentReplacePattern = replacePattern.replace(/#/g, headChar).replace(/\$&/g, headDefault);
	var text = inputText.replace(currentPattern, currentReplacePattern);
	
	currentPattern.compile(pattern.replace(/#/g, "\\"+normalChar),"gm");
	currentReplacePattern = replacePattern.replace(/#/g, normalChar).replace(/\$&/g, normalDefault)
	return text.replace(currentPattern, currentReplacePattern);
}

// n times text into an array
function multiple(text, n) {
	if (n <= 0) { return new Array(); }
	var tmp = new Array();
	for (var i = 0; i < n; i++) {
		tmp[i] = text;
	}
	return tmp;
}

// maximum number of columns in a table
function countColumns(table) {
	var maxColumns = 0;
	var lines = table.split("\n");
	var pattern = new RegExp("[\\^\\|]","g");

	for (var i = 0; i < lines.length; i++) {
		var result = lines[i].match(pattern);
		var columns = (result != null ? result.length : 0);
		maxColumns = (columns > maxColumns ? columns : maxColumns);
	}
	return maxColumns;
}

// add ^ or | symbols up to maxColumns
function normalizeTable(table) {
	var maxColumns = countColumns(table);
	var lines = table.split("\n");
	var headPattern = new RegExp("\\^","g");
	var normalPattern = new RegExp("\\|","g");
	for (var i = 0; i < lines.length; i++) {
		// resolve which character to add
		var c = lines[i].charAt(0); // ...using the first character
		// count column count on current line
		var columns = 0;
		if (c == "^") {
			result = lines[i].match(headPattern);
			columns = (result != null ? result.length : 0);
		} else if (c == "|") {
			result = lines[i].match(normalPattern);
			columns = (result != null ? result.length : 0);
		} else {
			break;	
		}
		
		//for (k = 0; k < lines[i].length; k++) {
		//	if (lines[i].charAt(k) == c) {
		//		columns++;
		//	}
		//}
		// add up to maxColumns count
		for (var j = 0; j < maxColumns - columns; j++) {		
			lines[i] += " " + c;
		}
	}
	return lines.join("\n");
}

function parseColumnOrder(orderString) {
	return (orderString != "" ? orderString.split(",") : new Array());
}

// Get position of the caret (cursor) in a field with given id 
function caretPosition(field) {
	//var field = document.getElementById(editId);
	
	if (field != null) {
		field.focus(); 
	  if (document.selection) {
	  	// IE
			return getCaretPositionIE(field);
	  } else if (field.selectionStart || field.selectionStart == '0') {
	  	// Mozilla / Netscape
	    return field.selectionStart;
	  }
  }
  return 0;
}

function getCaretPositionIE(textarea) {
	// Source: http://linebyline.blogspot.com/2006/11/textarea-cursor-position-in-internet.html
	// TODO:
	// - simplify a bit, we need only to compute startPoint
	// - find out what about the license... 

	
	//var textarea = document.getElementById("myTextArea");
	textarea.focus();
	var selection_range = document.selection.createRange().duplicate();
	
	if (selection_range.parentElement() != textarea) {
		// Check that the selection is actually in our textarea
		return 0;
	}
	// Create three ranges, one containing all the text before the selection,
	// one containing all the text in the selection (this already exists), and one containing all
	// the text after the selection.
	var before_range = document.body.createTextRange();
	before_range.moveToElementText(textarea);                    // Selects all the text
	before_range.setEndPoint("EndToStart", selection_range);     // Moves the end where we need it
	
	var after_range = document.body.createTextRange();
	after_range.moveToElementText(textarea);                     // Selects all the text
	after_range.setEndPoint("StartToEnd", selection_range);      // Moves the start where we need it
	
	var before_finished = false, selection_finished = false, after_finished = false;
	var before_text, untrimmed_before_text, selection_text, untrimmed_selection_text, after_text, untrimmed_after_text;
	
	// Load the text values we need to compare
	before_text = untrimmed_before_text = before_range.text;
	selection_text = untrimmed_selection_text = selection_range.text;
	after_text = untrimmed_after_text = after_range.text;
	
	// Check each range for trimmed newlines by shrinking the range by 1 character and seeing
	// if the text property has changed.  If it has not changed then we know that IE has trimmed
	// a \r\n from the end.
	do {
	  if (!before_finished) {
	      if (before_range.compareEndPoints("StartToEnd", before_range) == 0) {
	          before_finished = true;
	      } else {
	          before_range.moveEnd("character", -1)
	          if (before_range.text == before_text) {
	              untrimmed_before_text += "\r\n";
	          } else {
	              before_finished = true;
	          }
	      }
	  }
	  if (!selection_finished) {
	      if (selection_range.compareEndPoints("StartToEnd", selection_range) == 0) {
	          selection_finished = true;
	      } else {
	          selection_range.moveEnd("character", -1)
	          if (selection_range.text == selection_text) {
	              untrimmed_selection_text += "\r\n";
	          } else {
	              selection_finished = true;
	          }
	      }
	  }
	  if (!after_finished) {
	      if (after_range.compareEndPoints("StartToEnd", after_range) == 0) {
	          after_finished = true;
	      } else {
	          after_range.moveEnd("character", -1)
	          if (after_range.text == after_text) {
	              untrimmed_after_text += "\r\n";
	          } else {
	              after_finished = true;
	          }
	      }
	  }
	
	} while ((!before_finished || !selection_finished || !after_finished));
	
	// Untrimmed success test to make sure our results match what is actually in the textarea
	// This can be removed once you're confident it's working correctly
	var untrimmed_text = untrimmed_before_text + untrimmed_selection_text + untrimmed_after_text;
	var untrimmed_successful = false;
	if (textarea.value == untrimmed_text) {
	  untrimmed_successful = true;
	}
	// ** END Untrimmed success test
	
	var startPoint = untrimmed_before_text.length;
	var endPoint = startPoint + untrimmed_selection_text.length;
	var selected_text = untrimmed_selection_text;
	
	//alert("Start Index: " + startPoint + "\nEnd Index: " + endPoint + "\nSelected Text\n'" + selected_text + "'");
	return startPoint;
}

// Count columns of table under cursor given a textarea
function countTableColumns(fieldId) {
	field = document.getElementById(fieldId);
	if (field == null) { return 0; }
	var parts = extractTable(field);
	return countColumns(parts[1]);
}

// Prepare default value for order input field
function defaultOrderValue(tableFieldId) {
	if (tableFieldId == null) {
		return "";
	}
	var defaultOrder = new Array();
	var columns = countTableColumns(tableFieldId) - 1;
	columns = (columns >= 0 ? columns : 0); 
	for (var i = 0; i < columns; i++) {
		defaultOrder[i] = i + 1;
	}
	return defaultOrder.join(",");
}

// Find a continuous table which lies on "currentPosition" in "table".
// Return array [before table, table, after table] (all could be empty). 
function extractTable(field) {
	var table = field.value;
	var currentPosition = caretPosition(field);
	var emptyLine = "\n\n";
	if (/MSIE/.test(navigator.userAgent)) {
		emptyLine = "\r\n\r\n";
	}
	var startPos = table.lastIndexOf(emptyLine, currentPosition);
	startPos = (startPos != -1 ? (startPos + emptyLine.length) : 0); // +2 for \n\n
	var endPos = table.indexOf(emptyLine, currentPosition);
	endPos = (endPos != -1 ? endPos : table.length);
	var array = new Array();
	array[0] = table.substring(0, startPos);
	array[1] = table.substring(startPos, endPos);
	array[2] = table.substring(endPos, table.length);
	return array;
}

// Main editing function
function editTable(fieldId, order) {
	inputField = document.getElementById(fieldId);	
		var parts = extractTable(inputField);
	inputField.value = parts[0] + replaceColumns(normalizeTable(parts[1]),
		parseColumnOrder(order)) + parts[2];
}

// Callback for Dokuwiki toolbar button
function addBtnActionTableedit(btn, props, edid)
{
    eval("btn.onclick = function(){var order = defaultOrderValue('wiki__text');"
				+ "var result = prompt('"+jsEscape(props['prompt'])+"', order);"
				+ "if (result != null) {editTable('wiki__text', result)};return false;}");
    return true;
}
