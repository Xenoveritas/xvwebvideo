/**
 * Plugin for checking whether or not Flash is up to date on the current
 * browser.
 */
(function($){

/**
 * Platforms to ignore. I don't know what the full list should be, so right now,
 * it's just Android and the PS3, both of which Flash status is really dictated
 * by "who knows."
 */ 
var IGNORED = [ /\bAndroid\b/, /\bPLAYSTATION 3\b/i ];

function Version(s) {
	this.parts = s.split('.');
	// Translate to numbers
	for (var i = 0; i < this.parts.length; i++) {
		var n = Number(this.parts[i]);
		if (!isNaN(n)) {
			this.parts[i] = n;
		}
	}
}
Version.prototype = {
	compareTo: function(other) {
		var len = Math.min(this.parts.length, other.parts.length);
		for (var i = 0; i < len; i++) {
			var a = this.parts[i];
			var b = other.parts[i];
			if (typeof a != 'number' || typeof b != 'number') {
				// One or both are strings, do a string comparison instead.
				a = a.toString();
				b = b.toString();
			}
			// Use the comparators for the final type, since we'll have made
			// them strings.
			if (a < b)
				return -1;
			if (a > b)
				return 1;
		}
		// If we've entirely fallen through, we MAY be equal.
		if (this.parts.length == other.parts.length) {
			// Actually equal!
			return 0;
		}
		// Otherwise, the shorter version is considered less than, so 1.0 is
		// considered less than 1.0.0 and 1.0.1.
		if (this.parts.length < other.parts.length)
			return -1;
		else
			return 1;
	},
	toString: function() {
		return this.parts.join(".");
	}
}

var latestVersions = null;

/**
 * Checks whether or not Flash is up to date. This will invoke the callback
 * with one of the following strings depending on result:
 *
 * "not installed" - Flash is not installed at all (or was not detected)
 *    NOTE: latest version will be null in this case
 * "unknown" - Latest version information was unavailable, or none matched the
 *   current browser
 *    NOTE: latest version will be null in this case
 * "ignored" - The browser was detected as an embedded browser that we don't
 *   bother tracking user agents for
 *    NOTE: current and latest will be null for this.
 * "old" - Flash is not up to date
 * "latest" - Flash is the latest version
 * "newer" - Flash is newer than what we think is the latest version
 *
 * Callback is:
 *   callback(status, current, latest)
 *   status is as above, current is the detected version, latest is the latest
 */
function checkFlashUpToDate(url, callback, errorCallback) {
	for (var i = 0; i < IGNORED.length; i++) {
		if (IGNORED[i].test(navigator.userAgent)) {
			// Ignore this browser, it has an embedded Flash player
			//console.log("Ignoring " + navigator.userAgent);
			callback("ignored", null, null);
			return;
		}
	}
	// Is Flash even installed?
	var current = findFlashVersion();
	if (current == null) {
		callback("not installed", null, null);
		return;
	}
	if (latestVersions == null) {
		// Add "days since 1970" to the URL to avoid caching to some degree
		url = url + (url.indexOf('?') < 0 ? '?' : '&') + '_' + Math.floor(new Date().getTime() / (24*60*60*1000));
		$.ajax({
			"url": url,
			"dataType": "json",
			"error": function(jqXHR, textStatus, errorThrown) {
				if (errorCallback)
					errorCallback(jqXHR, textStatus, errorThrown);
			},
			"success": function(data, textStatus, jqXHR) {
				latestVersions = data;
				checkVersion(current, callback);
			}
		});
	} else {
		// Invoke immediately
		checkVersion(current, callback);
	}
}

function checkVersion(current, callback) {
	current = new Version(current);
	var agent = navigator.userAgent;
	var latest = null;
	// Figure out what the current version is
	for (var i = 0; i < latestVersions.length; i++) {
		var ver = latestVersions[i];
		if ("os" in ver) {
			// TODO: Possibly something more intelligent than this simple regexp
			// match.
			if (!new RegExp("\\b" + ver["os"] + "\\b").test(agent))
				continue;
		}
		// If we've fallen through, assume it matches.
		latest = new Version(ver["version"]);
		break;
	}
	if (latest == null)
		callback("unknown", current, null);
	var res = current.compareTo(latest);
	callback(res == 0 ? "latest" : (res < 0 ? "old" : "newer"), current, latest);
}

function findFlashVersion() {
	var plugins = navigator.plugins;
	for (var i = 0; i < plugins.length; i++) {
		if (/\bFlash\b/.test(plugins[i].name)) {
			return plugins[i].version;
		}
	}
	return null;
}

// Export:
window["checkFlashUpToDate"] = checkFlashUpToDate;
})(jQuery);
