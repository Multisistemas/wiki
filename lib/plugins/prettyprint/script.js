jQuery(function() {
	var $img = jQuery("#dokuwiki__header img").clone();
	var $h1 = jQuery("#dokuwiki__content h1:first");
	if ($h1.length > 0) {
		var h1 = $h1.text();
	} else {
		var h1 = '';
	}
	var $table = jQuery("<table>")
					.addClass("print-only")
					.css({
						'border-collapse': 'collapse',
						'border': '0',//eliminate default border
						'width':'100%', 
						'margin-bottom': '10px'
	});

	var $tr = jQuery("<tr>").appendTo($table);

	var cells = [];

	cells.push(jQuery("<td>").append($img));
	if (h1 !== '')
	{
		$new_h1 = $h1.clone();
		$h1.addClass("no-print");
		cells.push(jQuery("<td>").append($new_h1));
	}

	$publish = jQuery(".approval");
	if ($publish.length > 0) {

		var status = JSINFO['status'];
		var date = JSINFO['date'];
		var author = JSINFO['author'];

		var loc_status = jQuery('.approval_'+status).find("em").text();

		var $main_div = jQuery(".approval");

		if(status == 'approved')
			var cont = LANG.plugins.prettyprint.approve+'&nbsp;<strong>'+author+'</strong>';
		else
			var cont = LANG.plugins.prettyprint.created+'&nbsp;<strong>'+author+'</strong>';

		cells.push(jQuery("<td>").html('<p style="text-align:left">'+LANG.plugins.prettyprint.state+'&nbsp;<strong>'+loc_status+'</strong><br>'+
					LANG.plugins.prettyprint.date+'&nbsp;'+date.replace(' ', '&nbsp;')+'<br>'+cont+'</p>'));
	}
	for (cell in cells) {
		var $td = cells[cell];
		$td.css({
			'border':'2px solid #000', 
			'border-top':'0',
			'text-align': 'center',
			'vertical-align': 'middle'
		});
		$tr.append($td);
	}

	$tr.children().first().css('border-left', '0');
	$tr.children().last().css('border-right', '0');

	$table.prependTo("body");
});
