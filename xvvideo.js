// JavaScript for the video player.
//
// Originally this was some kind of wrapper around jwplayer, but as
// pretty much everything that needed to be wrapped is something
// video.js handles natively.
//
// Currently all this does is handle the "move to the next video"
// functionality.

$(function() {
	// When the page loads, grab the video.js player and bind to its
	// complete event.
	var player = videojs("video");
	player.on('ended', function() {
		if ($('#autonext').prop('checked')) {
			// Move on to the next video, if we have one.
			var next = $('#nav-buttons li.button-next a').attr('href');
			if (next) {
				location = next + (next.indexOf('?') >= 0 ? '&' : '?') + 'autoplay=on&autonext=on';
			}
		}
	});
});
