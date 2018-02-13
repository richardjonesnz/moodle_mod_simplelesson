define(['jquery','core/log'], function($,log){
    "use strict";
    log.debug('simplelesson toggle button loaded');
 
 return {
  	init:function() {
  		$('.mod_simplelesson_togglebutton').click(function() {
  			log.debug('toggling now');
  			$('.mod_simplelesson_togglebutton').toggle();
  		});
  	}
  };

 }
);