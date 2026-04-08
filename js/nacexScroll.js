jQuery(document).ready(function(){
	//Cargamos la página en mensaje de error (si existe)
	jQuery( window ).on('load', (function() {
		var ele = jQuery('#messages-nacex');
		if(ele.length) {
			jQuery('html,body').animate({scrollTop: ele.offset().top-150},'slow');
		}
	}));
});