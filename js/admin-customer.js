/**
 * All-in-one Rewards Module
 *
 * @author    Yann BONNAILLIE - ByWEB
 * @copyright 2012-2020 Yann BONNAILLIE - ByWEB (http://www.prestaplugins.com)
 * @license   Commercial license see license.txt
 * @category  Module
 * Support by mail  : contact@prestaplugins.com
 * Support on forum : Patanock
 * Support on Skype : Patanock13
 */

jQuery(function($){
	// templates
	$('.change_template').change(function() {
		$(this).parents('form').find('input[name="action"]').val($(this).attr('name'));
		$(this).parents('form').submit();
	});

	var filterFunctions = new Array();
	filterFunctions['rewards_list'] = {
	  	3 : function(e, n, f, i, $r, c, data) {
	  		var field = $r.find('td').eq(3).find('input');
	  		if (field.length > 0)
	    		return field.val().indexOf(f) > -1;
	    	else
	    		return e.indexOf(f) > -1;
	  	},
	  	4 : function(e, n, f, i, $r, c, data) {
	  		var field = $r.find('td').eq(4).find('select option:selected');
	  		if (field.length > 0)
	    		return $(field).text().toLowerCase().indexOf(f.toLowerCase()) > -1;
	    	else
	    		return e.toLowerCase().indexOf(f.toLowerCase()) > -1;
	  	}
	};
	filterFunctions['payments_list'] = {};
	filterFunctions['sponsorships_list'] = {
	  	6 : function(e, n, f, i, $r, c, data) {
	    	return $r.find('td').eq(6).find('input').val().indexOf(f) > -1;
	  	}
	};

	/* tablesorter */
	$('.tablesorter').each(function() {
		$(this).tablesorter({
			theme: 'ice',
			widthFixed: true,
			sortLocaleCompare: true,
			widgets: ['filter'],
			widgetOptions : {
				filter_functions : filterFunctions[$(this).attr('id')]
			},
			textExtraction: myTextExtraction
		}).tablesorterPager({
			container: $(this).next('.pager'),
			output: footer_pager,
			size: 10,
			savePages: false
		});
	});
});

var myTextExtraction = function(node)
{
	if ($(node).find('input[type=text]').length > 0) {
		return $(node).find('input[type=text]').val();
	} else if ($(node).find('select').length > 0) {
		return $(node).find('select option:selected').text();
	} else if ($(node).find('a').length > 0) {
		return $(node).find('a').text();
	}
	return node.innerHTML;
}

function initAutocomplete(version1_6) {
	if (version1_6) {
		$('#search_sponsor').autocomplete(
			sponsor_url,
			{
				cacheLength: 0,
				minChars: 2,
				width: 570,
				selectFirst: false,
				scroll: true,
				scrollHeight: 160,
				dataType: 'json',
				formatItem: function(item, i, max, value, term) {
					if ($('.autocomplete_header').length == 0)
						$('.ac_results').prepend('<div class="autocomplete_header"><span class="autocomplete_id_customer">'+idText+'</span><span class="autocomplete_firstname">'+firstnameText+'</span><span class="autocomplete_lastname">'+lastnameText+'</span><span class="autocomplete_email">'+emailText+'</span></div>');
					return '<a><span class="autocomplete_id_customer">'+item.id_customer+'</span><span class="autocomplete_firstname">'+item.firstname+'</span><span class="autocomplete_lastname">'+item.lastname+'</span><span class="autocomplete_email">'+item.email+'</span></a>';
				},
				parse: function(data) {
					var mytab = new Array();
					for (var i = 0; i < data.length; i++)
						mytab[mytab.length] = { data: data[i], value: data[i].id_customer };
					return mytab;
				}
			}
		)
		.result(function(event, data, formatted) {
			$('#new_sponsor').val(data.id_customer);
			$('#search_sponsor').val(data.firstname+' '+data.lastname);
		});
	} else {
		$('#search_sponsor').autocomplete({
			source: function(request, response) {
					$.getJSON(sponsor_url, request, function(data, status, xhr) {
					response($.map(data, function(item) {
						return {
							label: '<span class="autocomplete_id_customer">'+item.id_customer+'</span><span class="autocomplete_firstname">'+item.firstname+'</span><span class="autocomplete_lastname">'+item.lastname+'</span><span class="autocomplete_email">'+item.email+'</span>',
							value: item.firstname+' '+item.lastname,
							obj: item
						}
					}));
				});
			},
			minLength: 2,
			html: true,
			select: function(event, ui) {
				$('#new_sponsor').val(ui.item.obj.id_customer);
			}
		});

		/*
		* jQuery UI Autocomplete HTML Extension
		*
		* Copyright 2010, Scott González (http://scottgonzalez.com)
		* Dual licensed under the MIT or GPL Version 2 licenses.
		*
		* http://github.com/scottgonzalez/jquery-ui-extensions
		*/
		var proto = $.ui.autocomplete.prototype,
		initSource = proto._initSource;

		function filter( array, term ) {
	    	var matcher = new RegExp( $.ui.autocomplete.escapeRegex(term), "i" );
	      	return $.grep( array, function(value) {
	        	return matcher.test( $( "<div>" ).html( value.label || value.value || value ).text() );
	        });
		}

		$.extend( proto, {
	    	_initSource: function() {
				if ( this.options.html && $.isArray(this.options.source) ) {
					this.source = function( request, response ) {
						response( filter( this.options.source, request.term ) );
					};
				} else {
					initSource.call( this );
				}
			},
	        _renderItem: function( ul, item ) {
				return $( "<li></li>" )
					.data( "item.autocomplete", item )
					.append( $( "<a></a>" )[ this.options.html ? "html" : "text" ]( item.label ) )
					.appendTo( ul );
			},
			_renderMenu: function( ul, items ) {
	            var self = this;
	            ul.prepend('<div class="autocomplete_header"><span class="autocomplete_id_customer">'+idText+'</span><span class="autocomplete_firstname">'+firstnameText+'</span><span class="autocomplete_lastname">'+lastnameText+'</span><span class="autocomplete_email">'+emailText+'</span></div>');
	            $.each( items, function( index, item ) {
	                self._renderItem( ul, item );
	            });
	        }
		});
	}
}