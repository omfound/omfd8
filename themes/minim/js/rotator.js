(function ($, Drupal, window, document, undefined) {
  Drupal.behaviors.rotator =  {
    attach : function(context, settings) {
      $('.view-slides', context).once('rotator',
        function() {
          var $nav = $('<div/>').attr('id', 'rotator-nav');
          $(this).append($nav).find('.view-content').cycle({
	    pager: '#rotator-nav',
            delay: 5800
          });
        }
      );
    }
  }
})(jQuery, Drupal, this, this.document);
