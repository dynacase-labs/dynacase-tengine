#!/usr/bin/env python
# -*- coding: utf-8 -*-

import sys
import getopt
import uno

from com.sun.star.beans import PropertyValue
#Lets import page break as we will be doing a loop
from com.sun.star.style.BreakType import PAGE_BEFORE, PAGE_AFTER

def usage():
    print ""
    print "Usage: "+sys.argv[0]+"  -o <output_file> [-h <ooo_host>] [-p <ooo_port>] [-t <pdf|pdfa|html|doc>] -i <main input file> [<input files> ...]"
    print ""

ix=1
tin=[]
# for i in sys.argv:
#     print i
#     if i != "-o":
#         ix=ix+1
#         if (ix>2):
#             tin.append(i);
#         else:
#             input_file = i
#     else:
#        # ix=ix-1
#         break
    


try:
    opts, args = getopt.getopt(sys.argv[ix:], "i:o:h:p:t:")
except getopt.GetoptError, err:
    print str(err)
    usage()
    sys.exit(2)
    

input_file = ''
output_file = ''
ooo_host = '127.0.0.1'
ooo_port = '8123'
output_type = 'pdf'
for arg, val in opts:
    if arg == "-i":
        input_file = val
        print "Main input file is '%s'." % (input_file)
    elif arg == "-o":
        output_file = val
    elif arg == "-h":
        ooo_host = val
    elif arg == "-p":
        ooo_port = val
    elif arg == "-t":
        output_type = val
    else:
        usage()
        sys.exit(2)

tin=args
if input_file == '' or output_file == '':
    usage()
    sys.exit(2)
    
input_file_url = uno.systemPathToFileUrl(input_file)
output_file_url = uno.systemPathToFileUrl(output_file)

# Récupération d'un manager de service
context = uno.getComponentContext()
resolver = context.ServiceManager.createInstanceWithContext("com.sun.star.bridge.UnoUrlResolver", context)
ctx = resolver.resolve("uno:socket,host="+ooo_host+",port="+ooo_port+";urp;StarOffice.ComponentContext")
smgr = ctx.ServiceManager

# Input properties
properties = []
p = PropertyValue()
p.Name = "Hidden"
p.Value = True
properties.append(p)
properties = tuple(properties)

# Load the input document
desktop = smgr.createInstanceWithContext("com.sun.star.frame.Desktop", ctx)
doc = desktop.loadComponentFromURL(input_file_url, "_blank", 0, properties)



# Detect input document type
input_type = ''
if doc.supportsService("com.sun.star.text.GenericTextDocument"):
    input_type = 'writer'
elif doc.supportsService("com.sun.star.sheet.SpreadsheetDocument"):
    input_type = 'calc'
elif doc.supportsService("com.sun.star.presentation.PresentationDocument"):
    input_type = 'impress'
elif doc.supportsService("com.sun.star.presentation.DrawingDocument"):
    input_type = 'draw'
else:
    print "Could not find document type for '%s'." % (input_file)
    sys.exit(1)

# Select filterName
filterName = ''
if output_type == 'pdf' or output_type == 'pdfa':
    filterName = input_type+"_pdf_Export"
elif output_type == 'html':
    if input_type == 'writer':
        filterName = 'HTML (StarWriter)'
    elif input_type == 'calc':
        filterName = 'HTML (StarCalc)'
    elif input_type == 'impress':
        filterName = 'impress_html_Export'
elif output_type == 'odt':
    if input_type == 'writer':
        filterName = 'writer8'
elif output_type == 'doc':
    if input_type == 'writer':
        filterName = 'MS Word 97'
elif output_type == 'rtf':
    if input_type == 'writer':
        filterName = 'Rich Text Format'
elif output_type == 'txt':
    if input_type == 'writer':
        filterName = 'Text'
elif output_type == 'ods':
    if input_type == 'calc':
        filterName = 'calc8'
elif output_type == 'xls':
    if input_type == 'calc':
        filterName = 'MS Excel 97'
elif output_type == 'odp':
    if input_type == 'impress':
        filterName = 'impress8'
elif output_type == 'ppt':
    if input_type == 'impress':
        filterName = 'MS PowerPoint 97'
elif output_type == 'swf':
    if input_type == 'impress':
        filterName = 'impress_flash_Export'

if filterName == '':
    print "Could not find a valid output filter for converting '%s' to '%s'." % (input_file, output_type)
    sys.exit(1)

# Output properties
properties = []

# Set FilterName
p = PropertyValue()
p.Name = "FilterName"
p.Value = filterName
properties.append(p)

# Allow output file overwrite
p = PropertyValue()
p.Name = "Overwrite"
p.Value = True
properties.append(p)

if output_type == 'pdfa':
    # Set PDF/A specific options
    pFilterData = []
    prop = PropertyValue()
    prop.Name = "UseLossLessCompression"
    prop.Value = True
    pFilterData.append(prop)
    prop = PropertyValue()
    prop.Name = "SelectPdfVersion"
    prop.Value = 1
    pFilterData.append(prop)
    prop = PropertyValue()
    prop.Name = "FilterData"
    prop.Value = uno.Any(
        "[]com.sun.star.beans.PropertyValue",
        tuple(pFilterData)
        )
    properties.append(prop)

# Set properties and do the conversion
properties = tuple(properties)


cursor=doc.Text.createTextCursor()
cursor.gotoEnd(False)
cursor.BreakType = PAGE_BEFORE
tin.sort()
for i in tin:
    if len(i) > 1 :
#        print "merge "+i
        cursor.insertDocumentFromURL("file://"+i,())
        cursor.gotoEnd(False)
        cursor.BreakType = PAGE_BEFORE



# Update all indexes
if input_type == 'writer':
    doc.refresh()
    indexes = doc.getDocumentIndexes()
    indexesCount = indexes.getCount()
    if indexesCount != 0:
        for i in range(indexesCount):
            indexes.getByIndex(i).update()
            doc.refresh()
        # double pass for correct pages indexes
        for i in range(indexesCount):
            indexes.getByIndex(i).update()
            doc.refresh()


doc.storeToURL(output_file_url, properties)
doc.dispose()

sys.exit(0)
