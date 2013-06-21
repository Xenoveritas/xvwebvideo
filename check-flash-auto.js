/*
 * Auto-runs a little "check for Flash" script and displays a warning banner if
 * Flash is not up to date.
 *
 * Note that you'll need to set the "flash_latest.json" URL manually.
 *
 * To enable this feature, just cat this file onto the end of the xvvideo.js
 * file you're using anyway.
 *
 * Note that the "flash_latest.json" file is NOT kept up to date in this
 * repository. It's your responsibility to do that on your own.
 */

(function($) {
	$(function() {
		checkFlashUpToDate('/flash_latest.json', function(status, current, latest) {
			if (status == 'old') {
				// This is the only status we care about. Generate a warning
				// banner.
				var banner = $('<div class="flash-warning-banner"><p style="margin-top:0.5em"><strong>Warning:</strong> Your version of the Flash Player is out of date! It is highly recommended that you upgrade to the latest version to secure your computer.</p><p style="text-align:center"><a style="font-weight:bold;color:#FFF" href="http://www.adobe.com/go/getflashplayer">Update Flash Player</p></div>');
				banner.css({
					'width': '80%',
					'margin': '0',
					'padding': '0 1em',
					'background-color': '#800',
					'color': '#EEE',
					'font-size': '14pt',
					'font-family': 'Verdana,Tahoma,Arial,Helvetica,sans-serif',
					'border': 'solid 2px #DCC',
					'border-top': 'none',
					'position': 'fixed',
					'top': '0',
					'left': '10%'
				});
				banner.append($('<p/>').text('Your version is ' + current + ', latest version is ' + latest + '.').css('font-size', '60%'));
				$('body').append(banner);
				var button = $('<a href="#">X</a>');
				button.css({
					'float':'right',
					'width':'1em',
					'color': '#000',
					'background-color': '#EEE',
					'border': 'solid 2px #CCC',
					'text-align': 'center',
					'text-decoration': 'none',
					'margin-top': '0.5em'
				});
				button.click(function() {
					banner.remove();
					return false;
				});
				banner.prepend(button);
			}
		});
	});
})(jQuery);
