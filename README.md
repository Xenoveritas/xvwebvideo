xvwebvideo
==========

This is the code behind http://video.xenoveritas.org/ - for the most
part.

A script for encoding video exists in the "encoding" directory, but it's
old and I mostly don't use it any more. The primary pieces of interest
are instead the scripts behind running the video.

The play.php script embeds video information into a web page.

Required Software
-----------------

The basic code is written using PHP 5 and PHP will need to be available
on your server. It probably already is.

The code itself uses [video.js](http://videojs.com).

As of now, you need to compile a bunch of artifacts to build a final
useful system. Compiling requires:

 * `make` (possibly GNU make, but just any make should work)
 * Java (for the Closure Compiler)
 * [Closure Compiler](https://developers.google.com/closure/compiler/)
 * [nodejs](http://nodejs.org) (For LESS CSS and marked)
 * [LESS CSS](http://lesscss.org)
 * [marked](https://github.com/chjj/marked)

Once you have NodeJS installed, installing LESS CSS and marked is easy:

    npm install -g lessc
    npm install -g marked

To just create the minified versions of everything, just use `make`.

However, to create a "deploy" copy, you can use `make install` which
will create a version of all the files with their "deployed" versions.
You can set `video_root` to be the base URL where the video appears in
the final domain, or leave it blank if everything is at the root
already.

So, for example, to have everything hosted under `video`, use
`video_root=/video install`.

JSON Video Description Format
-----------------------------

The following are used to encode the video:

    {
      "title":       "Video Title",
      "description": "Description of the video",
      "url":         "video file name prefix",
      "formats":     [ "mp4", "webm" ],
      "poster":      "video.jpg",
      "width":       640, "height": 480,
      "duration":    600,
      "previous":    "prev",
      "next":        "next",
      "playlist":    "/playlist/videos"
    }

The individual keys are as follows:

### title

The title as shown on the page.

### description

A brief bit of text displayed under the video that describes the video.

### url

The URL to use for the video format, without an extension. The
extensions will automatically be added via the "formats" field. This URL
should not end with a '.' as it will be automatically added.

### formats

An array of extensions to add to the original URL, without the '.' at
the start.

### poster

Sets the poster image. Defaults to the URL with ".jpg" added to it, just
like the various video formats are defined.

### width / height

The width and height of the video file in pixels. Yes, all formats must
use the same width/height: each individual format is supposed to be a
reencode of the same video to provide support for multiple browsers.

### duration

The length of the video in seconds. If you really want to, this can be a
decimal, although that's ignored. This is purely informational.
(This was required with jwplayer and is entirely ignored by video.js.)

### previous

If provided, a relative URL to the previous video in the playlist.

### next

If provided, a relative URL to the next video in the playlist.

### playlist

NOT IMPLEMENTED YET
