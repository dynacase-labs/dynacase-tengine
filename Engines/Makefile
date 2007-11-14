# ============================================
# $Id: Makefile,v 1.7 2007/11/14 16:49:14 eric Exp $
#
# ============================================


appname = TE/engines
export appname
include $(utildir)/PubRule

DISTFILES +=  $(wildcard *2*) OOo-FreeDom.uno.pkg

$(pubdir)/$(appname) : 
	mkdir -p $@

$(pubdir)/$(appname)/%2txt : %2txt $(pubdir)/$(appname)
	cp $< $@
	chmod +x $@

$(pubdir)/$(appname)/%2pdf : %2pdf $(pubdir)/$(appname)	
	cp $< $@
	chmod +x $@

$(pubdir)/$(appname)/%2thumb : %2thumb $(pubdir)/$(appname)	
	cp $< $@
	chmod +x $@

publish:$(pubdir)/$(appname)/ooo2txt $(pubdir)/$(appname)/ooo2pdf $(pubdir)/$(appname)/txt2txt $(pubdir)/$(appname)/pdf2txt $(pubdir)/$(appname)/html2txt $(pubdir)/$(appname)/txt2pdf $(pubdir)/$(appname)/ooo2pdf $(pubdir)/$(appname)/ooo2thumb $(pubdir)/$(appname)/img2thumb



