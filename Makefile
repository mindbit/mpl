NAME := mpl
VERSION := $(shell grep '^Version:' $(NAME).spec | sed 's/[^0-9]*\([0-9\.]*\)[^0-9]*$$/\1/')
RPMDIR := $(shell rpm --eval %{_topdir})

.PHONY: rpm

rpm:
	cp -f $(NAME).spec $(RPMDIR)/SPECS
	git archive --format=tar --prefix $(NAME)/ HEAD | gzip > $(RPMDIR)/SOURCES/$(NAME)-$(VERSION).tar.gz
	rpmbuild -ba $(RPMDIR)/SPECS/$(NAME).spec
