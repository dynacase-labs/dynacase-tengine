TAR_DIST_NAME=freedom-te
TAR_DIST_DIR=freedom-te-$(VERSION)-$(RELEASE)

TAR_DIST_OPTS=--owner 0 --group 0

VERSION=$(shell head -1 VERSION)
RELEASE=$(shell head -1 RELEASE)

OBJECTS=

all:
	@echo ""
	@echo "  Available targets:"
	@echo ""
	@echo "    tarball"
	@echo "    clean"
	@echo ""

tarball:	
	mkdir -p tmp/$(TAR_DIST_DIR)
	tar -cf - \
		--exclude Makefile \
		--exclude tmp \
		--exclude test \
		--exclude mk.sh \
		--exclude $(TAR_DIST_NAME)-*-*.tar.gz \
		--exclude $(TAR_DIST_NAME)-*-*.autoinstall.php \
		--exclude "*~" \
		. | tar -C tmp/$(TAR_DIST_DIR) -xf -
	tar -C tmp -zcf $(TAR_DIST_NAME)-$(VERSION)-$(RELEASE).tar.gz $(TAR_DIST_OPTS) $(TAR_DIST_DIR)
	rm -Rf tmp

clean:
	find . -name "*~" -exec rm -f {} \;
	rm -Rf tmp
	rm -f $(TAR_DIST_NAME)-$(VERSION)-$(RELEASE).tar.gz
	rm -f $(TAR_DIST_NAME)-$(VERSION)-$(RELEASE).autoinstall.php