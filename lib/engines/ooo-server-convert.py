#!/usr/bin/env python
# -*- coding: utf-8 -*-

import sys
import getopt
import uno

from com.sun.star.beans import PropertyValue

def usage():
    print ""
    print "Usage: "+sys.argv[0]+" -i <input_file> -o <output_file> [-h <ooo_host>] [-p <ooo_port>] [-t <pdf|pdfa|html|doc>]"
    print ""
    
    return

def updateIndexes(doc):
    doc.refresh()
    indexes = doc.getDocumentIndexes()
    indexesCount = indexes.getCount()
    if indexesCount <= 0:
        return

    for i in range(indexesCount):
        indexes.getByIndex(i).update()
        doc.refresh()
        # double pass for correct pages indexes
        for i in range(indexesCount):
            indexes.getByIndex(i).update()
            doc.refresh()

    return

def embedWriterImages(smgr, ctx, doc):
    graphicProvider = smgr.createInstanceWithContext("com.sun.star.graphic.GraphicProvider", ctx)

    allImages = doc.GraphicObjects
    imagesCount = allImages.getCount();

    if imagesCount <= 0:
        return

    for i in range(imagesCount):
        imageObj = allImages.getByIndex(i)
        imageUrl = imageObj.GraphicURL

        if imageUrl.find("vnd.sun.star.GraphicObject:") == 0:
            continue

        p = PropertyValue()
        p.Name = "URL"
        p.Value = imageUrl

        imageObj.Graphic = graphicProvider.queryGraphic( (p,) )

    return

try:
    opts, args = getopt.getopt(sys.argv[1:], "i:o:h:p:t:")
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

if input_file == '' or output_file == '':
    usage()
    sys.exit(2)

try:    
    input_file_url = uno.systemPathToFileUrl(input_file)
except Exception as err:
    print "Error input_file_url: %s" % (str(err))
    sys.exit(1)

try:
    output_file_url = uno.systemPathToFileUrl(output_file)
except Exception as err:
    print "Error output_file_url: %s" % (str(err))
    sys.exit(1)

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
try:
    desktop = smgr.createInstanceWithContext("com.sun.star.frame.Desktop", ctx)
    doc = desktop.loadComponentFromURL(input_file_url, "_blank", 0, properties)
except Exception as err:
    print "Error loading input file '%s': %s" % (input_file_url, str(err))
    sys.exit(1)
    
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

# Update all indexes
if input_type == 'writer':
    try:
        updateIndexes(doc)
    except Exception as err:
        print "Error updating indexes: %s" % (str(err))
        raise

# Embed images in ODT documents
if output_type == 'odt':
    try:
        embedWriterImages(smgr, ctx, doc)
    except Exception as err:
        print "Error embedding images from '%s' in '%s': %s" % (input_file_url, output_file_url, str(err))
        raise

# Set properties and do the conversion
properties = tuple(properties)
try:
    doc.storeToURL(output_file_url, properties)
except Exception as err:
    print "Error storing '%s' to '%s': %s" % (input_file_url, output_file_url, str(err))
    sys.exit(1)

doc.dispose()

sys.exit(0)
