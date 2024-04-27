/* To avoid CSS expressions while still supporting IE 7 and IE 6, use this script */
/* The script tag referencing this file must be placed before the ending body tag. */

/* Use conditional comments in order to target IE 7 and older:
	<!--[if lt IE 8]><!-->
	<script src="ie7/ie7.js"></script>
	<!--<![endif]-->
*/

(function() {
	function addIcon(el, entity) {
		var html = el.innerHTML;
		el.innerHTML = '<span style="font-family: \'sas\'">' + entity + '</span>' + html;
	}
	var icons = {
		'sas-icon-calendar-input': '&#xe906;',
		'sas-icon-phone': '&#xe904;',
		'sas-icon-filter': '&#xe905;',
		'sas-icon-remove': '&#xe903;',
		'sas-icon-information': '&#xe901;',
		'sas-icon-trash': '&#xe902;',
		'sas-icon-check': '&#xe900;',
		'sas-icon-calendar': '&#xe974;',
		'sas-icon-pencil': '&#xe979;',
		'sas-icon-sun': '&#xe975;',
		'sas-icon-search': '&#xe907;',
		'sas-icon-info-circle': '&#xe908;',
		'sas-icon-burger-menu': '&#xe909;',
		'sas-icon-back': '&#xe90a;',
		'sas-icon-connection': '&#xe90b;',
		'sas-icon-emergency': '&#xe90c;',
		'sas-icon-first': '&#xe90d;',
		'sas-icon-last': '&#xe90e;',
		'sas-icon-cnam': '&#xe90f;',
		'0': 0
		},
		els = document.getElementsByTagName('*'),
		i, c, el;
	for (i = 0; ; i += 1) {
		el = els[i];
		if(!el) {
			break;
		}
		c = el.className;
		c = c.match(/sas-icon-[^\s'"]+/);
		if (c && icons[c[0]]) {
			addIcon(el, icons[c[0]]);
		}
	}
}());
