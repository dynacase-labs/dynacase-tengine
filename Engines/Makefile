# ============================================
# $Id: Makefile,v 1.5 2007/11/13 16:34:41 eric Exp $
# $Log: Makefile,v $
# Revision 1.5  2007/11/13 16:34:41  eric
# Add script for txt2pdf
#
# Revision 1.4  2007/09/21 08:13:30  eric
# ooo2pdf
#
# Revision 1.3  2007/06/12 12:49:19  eric
# add ooo uno macro
#
# Revision 1.2  2007/06/04 16:23:26  eric
# add and testing engines
#
# Revision 1.1  2007/05/30 15:54:22  eric
# use engine command
#
# Revision 1.1  2007/05/29 13:33:15  eric
# add command GET
#
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

publish:$(pubdir)/$(appname)/ooo2txt $(pubdir)/$(appname)/ooo2pdf $(pubdir)/$(appname)/txt2txt $(pubdir)/$(appname)/pdf2txt $(pubdir)/$(appname)/html2txt $(pubdir)/$(appname)/txt2pdf $(pubdir)/$(appname)/ooo2pdf



