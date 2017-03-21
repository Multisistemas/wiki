/**
 * DokuWiki Plugin workflow browser script
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Ryan Boder <ryan.boder@suretycam.com>
 */

var wfstate = null;
var wfrows = null;

function wfCount(obj) {
    var i = 0;
    for (var j in obj)
	i++;
    return i;
}

function readSessionObject(name) {
    var str = jQuery.cookie(name);
    if (str)
	return jQuery.parseJSON(str);
    else
	return {};
}

function writeSessionObject(name, obj) {
    jQuery.cookie(name, JSON.stringify(obj));
}

function wfRemember(name, value) {
    wfstate[name] = value;
    writeSessionObject('wfstate', wfstate);
}

function wfForget(name) {
    delete wfstate[name];
    writeSessionObject('wfstate', wfstate);
}

function wfForgetAll() {
    wfstate = {};
    writeSessionObject('wfstate', wfstate);
}

function wfRenderLink(target, script, text) {
    return '<a class="wikilink1" href="doku.php?id=' + wfnamespace + ':' + target + '" onclick="' + script + '">' + text + '</a>';
}

function wfFilterRowsByState() {
    wfrows.filter(function() {
	var data = jQuery(this).attr('data-criteria');
	if (data != null && data != '') {
	    var criteria = data.split(':');
	    for (var i in criteria) {
		var crit = criteria[i].split('=');
		var values = crit[1].split(',');
		for (var j in wfstate) {
		    // if we know state for this criteria and it does not match any valid values...
		    if (crit[0] == j && values.indexOf(wfstate[j]) == -1) {
			return true;
		    }
		}
	    }
	}		
        return false;
    }).hide();
}

function wfFocusSearch() {
    setTimeout(function (){
	jQuery('#wfsearch').focus();
    }, 400);
}


jQuery(document).ready(function() {
    if (typeof wfnamespace == 'undefined')
	return; // don't do anything if this isn't a workflow page
    
    if (wfpageid == 'start') { // if this is the start page, store the state definitions for other pages to use	
	writeSessionObject('wfstatedefs', wfstatedefs);
    } else { // otherwise, read the state definitions that were defined in the start page	
	wfstatedefs = readSessionObject('wfstatedefs');
    }
    
    // recall the current state in this session
    wfstate = readSessionObject('wfstate');
    
    // render the forget state links at the bottom of the decision link table
    for (var name in wfstate) {
	var link = wfRenderLink(wfpageid, 'wfForget(\'' + name + '\');', name + ' = ' + wfstate[name]);
	jQuery('#wftable tr:last').after('<tr><td>Forget ' + link  + '</td></tr>');
    }
    if (wfCount(wfstate) > 0)
	jQuery('#wftable tr:last').after('<tr><td>' + wfRenderLink('start', 'wfForgetAll();', 'Forget everything and start over.') + '</td></tr>');
    jQuery('#wftable').append('<tr><td>' + wfRenderLink('start', '', 'Start over.') + '</td></tr>');
    
    // render the remember state links at the bottom of the decision link table
    for (var name in wfstatedefs) {
	if (wfstate.hasOwnProperty(name))
	    continue; // don't render a link if this state is already remembered
	var values = wfstatedefs[name].split(',');
	var links = '';
	for (var i in values) {
	    links += wfRenderLink(wfpageid, 'wfRemember(\'' + name + '\',\'' + values[i] + '\');', values[i]) + ' | ';
	}
	if (links.length > 0)
	    links = links.slice(0, -3); // remove the extraneous vertical bar after the last value
	jQuery('#wftable tr:last').after('<tr><td>Remember ' + name + ' = ' + links + '</td></tr>');
    }
    
    // select the rows after we've rendered the forget state links
    wfrows = jQuery('#wftable tr');
    
    // filter the decision table every time the user types in the search box
    jQuery('#wfsearch').keyup(function() {
	var val = '^(?=.*\\b' + jQuery.trim(jQuery(this).val()).split(/\s+/).join('\\b)(?=.*\\b') + ').*$', reg = RegExp(val, 'i'), text;
	wfrows.show().filter(function() {
            text = jQuery(this).text().replace(/\s+/g, ' ');
            return !reg.test(text);
	}).hide();
	wfFilterRowsByState();
    });
    
    // filter out any decision links due to state criteria when the page first loads
    wfFilterRowsByState();
});
