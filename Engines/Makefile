# ============================================
# $Id: Makefile,v 1.12 2008/11/21 16:57:59 jerome Exp $
#
# ============================================


appname = TE/engines
export appname
include $(utildir)/PubRule

DISTFILES +=  $(wildcard *2*) OOo-FreeDom.uno.pkg


$(pubdir)/$(appname)/%pkg : %pkg
	cp $< $@

$(pubdir)/$(appname)/%2txt : %2txt $(pubdir)/$(appname)
	cp $< $@
	chmod +x $@

$(pubdir)/$(appname)/%2pdf : %2pdf $(pubdir)/$(appname)	
	cp $< $@
	chmod +x $@

$(pubdir)/$(appname)/%2pdfa : %2pdfa $(pubdir)/$(appname)	
	cp $< $@
	chmod +x $@

$(pubdir)/$(appname)/%2odt : %2odt $(pubdir)/$(appname)	
	cp $< $@
	chmod +x $@

$(pubdir)/$(appname)/%2thumb : %2thumb $(pubdir)/$(appname)	
	cp $< $@
	chmod +x $@

$(pubdir)/$(appname)/te-xvfb-run : te-xvfb-run $(pubdir)/$(appname)
	cp $< $@
	chmod +x $@

publish: $(pubdir)/$(appname)/te-xvfb-run $(pubdir)/$(appname)/ooo2txt $(pubdir)/$(appname)/ooo2pdf $(pubdir)/$(appname)/ooo2pdfa $(pubdir)/$(appname)/txt2txt $(pubdir)/$(appname)/pdf2txt $(pubdir)/$(appname)/html2txt $(pubdir)/$(appname)/txt2pdf $(pubdir)/$(appname)/ooo2pdf $(pubdir)/$(appname)/html2pdf $(pubdir)/$(appname)/html2pdfa $(pubdir)/$(appname)/ooo2thumb $(pubdir)/$(appname)/img2thumb $(pubdir)/$(appname)/OOo-FreeDom.uno.pkg $(pubdir)/$(appname)/html2odt



