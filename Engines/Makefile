# ============================================
# $Id: Makefile,v 1.2 2007/06/04 16:23:26 eric Exp $
# $Log: Makefile,v $
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

DISTFILES +=  $(wildcard *2*) 

$(pubdir)/$(appname) : 
	mkdir -p $@

$(pubdir)/$(appname)/txt2txt : txt2txt $(pubdir)/$(appname)
	cp $< $@
	chmod +x $@
$(pubdir)/$(appname)/ooo2txt : ooo2txt $(pubdir)/$(appname)
	cp $< $@
	chmod +x $@
$(pubdir)/$(appname)/pdf2txt : pdf2txt $(pubdir)/$(appname)
	cp $< $@
	chmod +x $@
$(pubdir)/$(appname)/html2txt : html2txt $(pubdir)/$(appname)
	cp $< $@
	chmod +x $@

publish:$(pubdir)/$(appname)/ooo2txt $(pubdir)/$(appname)/txt2txt $(pubdir)/$(appname)/pdf2txt $(pubdir)/$(appname)/html2txt



