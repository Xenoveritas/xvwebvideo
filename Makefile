all: minify README.html

# TODO: Configure these
CLOSURE_JAR=compiler.jar
JAVA=java
CLOSURE=$(JAVA) -jar "$(CLOSURE_JAR)" --compilation_level ADVANCED_OPTIMIZATIONS
LESSC=lessc
MARKED=marked

INSTALL_DIR=deploy
video_root ?=

JQUERY_EXTERNS=

VIDEO_JS_FILES = $(addprefix video-js/,video.js video-js.min.css video-js.swf video-js.png)

minify: setup-closure xvvideo.min.js check-flash.min.js site.min.css

xvvideo.min.js: xvvideo.js
	$(CLOSURE) --js xvvideo.js --js_output_file xvvideo.min.js $(JQUERY_EXTERNS)

check-flash.min.js: check-flash.js check-flash-auto.js
	$(CLOSURE) --js check-flash.js --js check-flash-auto.js --js_output_file check-flash.min.js $(JQUERY_EXTERNS)

site.min.css: site.less
	$(LESSC) -x site.less > site.min.css

README.html: README.md site.min.css
	echo "<DOCTYPE html><html><head><title>xvwebvideo README</title><link rel="stylesheet" type="text/css" href="site.min.css"></head><body>" > README.html
	$(MARKED) < README.md >> README.html
	echo "</body></html>" >> README.html

setup-closure: setup-java
# Make sure there is a compiler.jar file
	@if [ ! -e $(CLOSURE_JAR) ] ; then \
		echo "Closure compiler not found!"; \
		echo "Please copy the closure compiler JAR to this directory."; \
		echo "See: https://developers.google.com/closure/compiler/"; \
		exit 1; \
	fi

setup-java:
# Make sure there is a Java and that it can run.
	@if ! $(JAVA) -version >/dev/null 2>&1; then echo "No installed Java found!"; exit 1; fi

clean:
	rm -f xvvideo.min.js check-flash.min.js site.min.css README.html

install: all
	@if [ -z "$(INSTALL_DIR)" ] || [ "$(INSTALL_DIR)" = "." ] ; then \
		echo "Cowardly refusing to install into self."; \
		exit 1; \
	fi
	mkdir -p $(INSTALL_DIR)/video-js
	cp $(VIDEO_JS_FILES) $(INSTALL_DIR)/video-js
	mkdir -p $(INSTALL_DIR)/video-js/font
	cp video-js/font/vjs* $(INSTALL_DIR)/video-js/font
	mkdir -p $(INSTALL_DIR)/script
	cp jquery-2.0.2.min.js xvvideo.min.js $(INSTALL_DIR)/script
	mkdir -p $(INSTALL_DIR)/style
	cp site.min.css $(INSTALL_DIR)/style
	mkdir -p $(INSTALL_DIR)/play
	sed -e "s:_URL', '/script:_URL', '$(video_root)/script:" \
		-e "s:'VIDEO_JS_ROOT', '/video-js':'VIDEO_JS_ROOT', '$(video_root)/video-js':" \
		-e "s:'CSS_SHEETS', ':'CSS_SHEETS', '$(video_root):" \
		-e "s:'VIDEO_ROOT', '/':'VIDEO_ROOT', '$(video_root)/':" \
		< play.php > $(INSTALL_DIR)/play/play.php
	sed -e "s:/play/play.php:$(video_root)/play/play.php:" \
		< sample.htaccess > $(INSTALL_DIR)/play/.htaccess
	sed -e "s:site.min.css:$(video_root)/style/site.min.css:" \
		< stub.html > $(INSTALL_DIR)/index.html

.PHONY: all clean setup-closure setup-java minify install
