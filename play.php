<?php
#   Copyright 2010, 2011 Daniel Potter
#
#   Licensed under the Apache License, Version 2.0 (the "License");
#   you may not use this file except in compliance with the License.
#   You may obtain a copy of the License at
#
#       http://www.apache.org/licenses/LICENSE-2.0
#
#   Unless required by applicable law or agreed to in writing, software
#   distributed under the License is distributed on an "AS IS" BASIS,
#   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#   See the License for the specific language governing permissions and
#   limitations under the License.
#

#
# Configuration options
#
# You'll likely need to change these for your site. (NOTE: None of these are
# escaped. If you intend to use paths that use non-alphanumeric characters,
# DON'T. Just don't. You can get around by escaping them as a URL component,
# but... don't.)
#

# Prepended to all video paths
define('VIDEO_ROOT', '/');

# Prepended to the path to the Video.js library files
define('VIDEO_JS_ROOT', '/video-js');

# The URL to load the JavaScript for the video player.
define('XVVIDEO_URL', '/script/xvvideo.rollup.js');

# The stylesheet to use
define('CSS_SHEETS', '/style/site.min.css');

# The name of the cookie to use - should be alphanumeric characters, as this
# will be inserted into a JavaScript string.
define('COOKIE_NAME', 'xvvideo');

# The amount of time in seconds after which the cookie expires.
define('COOKIE_EXPIRES', 365*24*60*60);

# The path to use for the cookie, see
# <http://www.php.net/manual/en/function.setcookie.php> for details on what it
# means. It's safe to leave this at '/'.
define('COOKIE_PATH', '/');

# The following are "magic" arrays for dealing with extensions.
# For the most part you probably don't need to play with them.
#
# mime_magic maps extensions to MIME types
# names provides the name displayed for downloading the file in the page

$mime_magic = array(
	"mp4" => "video/mp4",
	"iphone.mp4" => "video/mp4",
	"webm" => "video/webm; codecs=\"vp8, vorbis\"",
	"ogv" => "video/ogg; codecs=\"theora, vorbis\""
);

$names = array(
	"mp4" => "MP4",
	"iphone.mp4" => false,
	"webm" => "WebM",
	"ogv" => "Theora"
);

#
# End configuration options
#

# Used to indicate whether options where changed to avoid unnecessarily sending
# cookies.
$set_cookie = false;

# Grab the current cookie, if any
if (array_key_exists(COOKIE_NAME, $_COOKIE)) {
	$cookie = $_COOKIE[COOKIE_NAME];
	$t = explode(',', $cookie);
	$cookie = array();
	foreach ($t as $value) {
		$i = strpos($value, ':');
		if ($i === FALSE) {
			# store the value directly as a blank string
			$cookie[$value] = '';
		} else {
			$cookie[substr($value, 0, $i)] = substr($value, $i+1);
		}
	}
} else {
	$cookie = array();
}

# Gets whether an option is enabled, first using the query string, then using
# cookies (if any are set).

function get_option($name, $default=false) {
	global $set_cookie, $cookie;
	if (array_key_exists($name, $_GET)) {
		$value = $_GET[$name];
		if ($value == 'on') {
			$value = true;
		} else {
			$value = false;
		}
		$set_cookie = true;
		return $value;
	} else {
		if (array_key_exists($name, $cookie)) {
			$value = $cookie[$name];
			if ($value == 'on') {
				$value = true;
			} else {
				$value = false;
			}
			return $value;
		}
	}
	return $default;
}

$html5 = get_option('html5', true);
$autonext = get_option('autonext');

if ($set_cookie) {
	$value = 'html5:' . ($html5 ? 'on' : 'off');
	$value .= ',autonext:' . ($autonext ? 'on' : 'off');
	# As far as I can tell, attempting to set the domain flat-out doesn't work.
	# So just don't bother trying.
	setcookie(COOKIE_NAME, $value, time()+COOKIE_EXPIRES, COOKIE_PATH);
}

# Simple function for resolving a path to a base path.
function resolve_path($base, $path) {
	# Explode path by path separators, or re-use the existing base path.
	if (is_array($base)) {
		$path_arr = $base;
	} else {
		$path_arr = explode('/', $base);
	}

	# Go through the base path and make sure there are no '.' or '..'
	# components, if there are, fail immediately. (Potential security flaw.)
	foreach ($path_arr as &$elem) {
		if ($elem == '.' || $elem == '..' || $elem == '') {
			throw new Exception('Base path contains relative path components');
		}
	}
	# Remove the file part from the base (a path ending in '/' will contain an
	# empty string at the end)
	array_pop($path_arr);
	$child_path = explode('/', $path);
	foreach ($child_path as &$comp) {
		if ($comp == '..') {
			if (count($path_arr) == 0) {
				throw new Exception('Child path descends above root!');
			}
			array_pop($path_arr);
		} else if ($comp == '.' || $comp == '') {
			# Eat this in both cases.
		} else {
			array_push($path_arr, $comp);
		}
	}
	# And use this URL
	return implode('/', $path_arr);
}

function parse_video_metadata($path) {
	$path_arr = explode('/', $path);

	# Go through the path and make sure there are no '.' or '..' components

	foreach ($path_arr as &$elem) {
		if ($elem == '.' || $elem == '..' || $elem == '') {
			return 404;
		}
	}

	# FIXME: Allow the video metadata path to be set
	$json_path = '../' . $path . '.json';
	# Not checking if the file exists causes a warning to be sent if it
	# doesn't. I'm going to call that "by design" since it can be helpful
	# when debugging, but I suppose it might clog up the error log file.
	$json = file_get_contents($json_path, false, NULL, NULL, 8192);

	if ($json === FALSE) {
		return 404;
	}

	$video = json_decode($json);

	if (!is_object($video)) {
		return 500;
	}

	# While we're in this function, also handle relative URLs within the
	# video metadata.

	if (is_string($video->url)) {
		# Resolve the URL as relative to the path
		try {
			$video->url = resolve_path($path_arr, $video->url);
		} catch (Exception $e) {
			return 500;
		}
	}
	if (is_string($video->poster)) {
		# Resolve this relative to the path
		try {
			$video->poster = resolve_path($path_arr, $video->poster);
		} catch (Exception $e) {
			return 500;
		}
	}
	return $video;
}

function version_footer() {
	echo '<div id="version"><a href="https://github.com/Xenoveritas/xvwebvideo">xvwebvideo</a> 0.5; <a href="http://www.videojs.com/">video.js</a> 4.0</div>';
}

$path = $_GET['q'];

$video = parse_video_metadata($path);

if (!is_object($video)) {
	if ($video == 500) {
		header("HTTP/1.0 500 Internal Server Error");
		$title = "Internal Server Error";
		$desc = "The video metadata on the server could not be parsed.";
	} else {
		header("HTTP/1.0 404 Not Found");
		$title = "Video Not Found";
		$desc = "The requested video could not be found.";
	}
	echo '<!DOCTYPE html>

<html><head><title>', $title, '</title><link rel="stylesheet" type="text/css" href="', CSS_SHEETS, '"></head><body><h1>', $title, '</h1><p>', $desc, '</p>';
	version_footer();
	echo '</body></html>';
} else {
	$title = is_string($video->title) ? $video->title : "Untitled";
	$width = is_int($video->width) ? $video->width : 400;
	$height = is_int($video->height) ? $video->height : 300;
	$formats = $video->formats;
	header("Content-Type: text/html; charset=UTF-8");
	if (is_string($video->url)) {
		$path = VIDEO_ROOT . $video->url;
	} else {
		$path = VIDEO_ROOT . $path;
	}
	$poster = is_string($video->poster) ? VIDEO_ROOT . $video->poster : $path . '.jpg';
?>
<!DOCTYPE html>

<html>
<head>
<?php/*<!--<?php
	# DEBUG (cookie is always set, it's just empty if none was found, but whatever):
	echo "Got cookie:\n";
	foreach ($cookie as $key => $value) {
		echo '[', $key, '] => [', $value, "]\n";
	}
?>-->*/?>
<title><?php echo $title;?></title>
<link rel="stylesheet" type="text/css" href="<?php echo CSS_SHEETS; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo VIDEO_JS_ROOT; ?>/video-js.min.css">
<!-- Video.js needs to be included in <head> -->
<script type="application/javascript" src="<?php echo VIDEO_JS_ROOT; ?>/video.js"></script>
<script type="application/javascript">
  videojs.options.flash.swf = "<?php echo VIDEO_JS_ROOT; ?>/video-js.swf";
</script>
</head>
<body>
<h1><?php echo $title;?></h1>

<div id="video-container">
<?php
	if ((!is_array($formats)) || count($formats) == 0) {
		echo 'No video formats defined.';
	} else {
		if ($_GET['autoplay'] == 'on') {
			$autoplay = " autoplay";
		} else {
			$autoplay = "";
		}
		if ($html5) {
			$dataSetup = '{"techOrder":["html5","flash"]}';
		} else {
			$dataSetup = '{"techOrder":["flash","html5"]}';
		}
	?>
	<video id="video" class="video-js vjs-default-skin"
	       controls preload="auto"<?php echo $autoplay;?>
	       width="<?php echo $width; ?>"
	       height="<?php echo $height; ?>"
	       poster="<?php echo $poster; ?>"
	       data-setup="<?php echo htmlspecialchars($dataSetup);?>">
		<noscript>You must have JavaScript enabled in order to load the video
		player. The following also applies:</noscript>
		Either a Flash-capable (recommended) or HTML5 Video capable browser is
		required to view the video. Get the <a
			href="http://www.adobe.com/go/getflashplayer">Adobe Flash Player</a>.
	<p>You may also download the video using the link<?php if (count($formats) > 1) {?>s<?php }?> provided below.</p>
<?php
		foreach ($formats as $format) {
			echo "\t\t<source src=\"", $path, '.' , $format, "\"";
			$type = $mime_magic[$format];
			if (isset($type)) {
				echo " type=\"", htmlspecialchars($type), "\">\n";
			}
		}
?>
	</video>
</div>
<?php
}
if (isset($video->previous) || isset($video->next)) {
	echo '<ul class="nav-buttons" id="nav-buttons">';
	echo '<li class="button button-previous';
	if (isset($video->previous)) {
		echo '"><a href="', htmlspecialchars($video->previous), '">Previous</a>';
	} else {
		echo ' disabled">Previous';
	}
	echo '</li> <li class="button button-next';
	if (isset($video->next)) {
		echo '"><a href="', htmlspecialchars($video->next), '">Next</a>';
	} else {
		echo ' disabled">Next &#9658;';
	}
	echo '</li></ul>';
} ?>

<div id="metadata">
<?php if (isset($video->description)) {
	echo '<div id="description">';
	echo $video->description;
	echo '</div>';
}
if ((!is_array($formats)) || count($formats) == 0) {
	# echo "No video formats defined.";
} else {
	echo "<div id=\"download\">Download as:";
	foreach ($formats as $format) {
		$name = $names[$format];
		if ($name === FALSE) {
			# Don't display videos of this type
			continue;
		}
		echo " <a href=\"$path.$format\">";
		echo isset($name) ? $name : strtoupper($format);
		echo "</a>";
	}
	echo "</div>";
}
?>
</div>
<div id="options">
<form method="GET" action="">
<?php
# If there is a next video, display the auto-play option
if (isset($video->next)) {
	?><input type="checkbox" id="autonext" name="autonext" value="on"<?php if ($autonext) { echo ' checked'; }?>>
<label for="autonext">Automatically play next video when this video completes</label><?php
}
?>
</form>
</div>
<div id="html5">
<?php
# FIXME: We should only display the toggle if Flash mode is available, which,
# at present, really can't be detected.
if ($html5) {
	echo 'Using HTML5 by default. You may try <a href="?html5=off">forcing Flash mode</a> if this fails.';
} else {
	echo 'Using Flash by default. <a href="?html5=on">Return to HTML5 mode</a>';
}
?>
<div id="html5_using"></div>
</div>
<?php version_footer(); ?>
<script type="application/javascript" src="<?php echo XVVIDEO_URL; ?>"></script>
</body>
</html>
<?php
}
?>
