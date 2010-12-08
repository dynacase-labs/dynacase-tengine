package com.anakeen.oooserver;

import java.io.File;
import java.io.BufferedInputStream;
import java.io.ByteArrayOutputStream;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.HashMap;

import com.sun.star.beans.PropertyValue;
import com.sun.star.beans.XPropertySet;
import com.sun.star.bridge.XBridge;
import com.sun.star.bridge.XBridgeFactory;
import com.sun.star.bridge.XUnoUrlResolver;
import com.sun.star.frame.XComponentLoader;
import com.sun.star.frame.XController;
import com.sun.star.frame.XDispatchHelper;
import com.sun.star.frame.XDispatchProvider;
import com.sun.star.frame.XFrame;
import com.sun.star.frame.XModel;
import com.sun.star.frame.XStorable;
import com.sun.star.graphic.XGraphicProvider;
import com.sun.star.lang.XMultiComponentFactory;
import com.sun.star.style.BreakType;
import com.sun.star.text.XSimpleText;
import com.sun.star.text.XTextCursor;
import com.sun.star.text.XTextDocument;
import com.sun.star.text.XTextGraphicObjectsSupplier;
import com.sun.star.uno.UnoRuntime;
import com.sun.star.uno.XComponentContext;
import com.sun.star.comp.helper.Bootstrap;
import com.sun.star.connection.XConnection;
import com.sun.star.connection.XConnector;
import com.sun.star.container.XNameAccess;
import com.sun.star.document.XDocumentInsertable;
import com.sun.star.document.XEventListener;
import com.sun.star.lang.EventObject;
import com.sun.star.lang.XComponent;
import com.sun.star.lang.XServiceInfo;
import com.sun.star.ucb.XFileIdentifierConverter;
import com.sun.star.util.XCloseable;
import com.sun.star.util.XRefreshable;

public class OOoServer implements XEventListener {
	public boolean debug = false;

	public String host = "";
	public String port = "";
	public boolean connected = false;
	public boolean inDisconnect = false;

	public XComponentContext localContext;
	public XComponentContext remoteContext;
	public XMultiComponentFactory localServiceManager;
	public XMultiComponentFactory remoteServiceManager;
	private XComponent bridgeComponent;
	public XBridge bridge;

	OOoServer(String ooo_host, String ooo_port) {
		this.host = ooo_host;
		this.port = ooo_port;
	}

	public synchronized void connect() throws Exception {
		if (this.debug) {
			System.err.print("Connecting to " + this.host + ":" + this.port
					+ "\n");
		}

		/* Boot with local context/service manager */
		this.localContext = Bootstrap.createInitialComponentContext(null);
		this.localServiceManager = this.localContext.getServiceManager();
		Object unoUrlResolver = this.localServiceManager
				.createInstanceWithContext(
						"com.sun.star.bridge.UnoUrlResolver", this.localContext);
		XUnoUrlResolver urlResolver = (XUnoUrlResolver) UnoRuntime
				.queryInterface(XUnoUrlResolver.class, unoUrlResolver);

		/* Get a connector */
		XConnector connector = (XConnector) UnoRuntime.queryInterface(
				XConnector.class, localServiceManager
						.createInstanceWithContext(
								"com.sun.star.connection.Connector",
								localContext));

		/* Connect to OOo over TCP/IP */
		XConnection connection = connector.connect("socket,host=" + this.host
				+ ",port=" + this.port + ",tcpNoDelay=1");

		/* Get a bridge */
		XBridgeFactory bridgeFactory = (XBridgeFactory) UnoRuntime
				.queryInterface(XBridgeFactory.class, localServiceManager
						.createInstanceWithContext(
								"com.sun.star.bridge.BridgeFactory",
								localContext));
		this.bridge = bridgeFactory.createBridge("", "urp", connection, null);
		this.bridgeComponent = (XComponent) UnoRuntime.queryInterface(
				XComponent.class, this.bridge);
		this.bridgeComponent.addEventListener(this);

		/* Get the remote service manager and context */
		this.remoteServiceManager = (XMultiComponentFactory) UnoRuntime
				.queryInterface(XMultiComponentFactory.class,
						this.bridge.getInstance("StarOffice.ServiceManager"));

		XPropertySet properties = (XPropertySet) UnoRuntime.queryInterface(
				XPropertySet.class, this.remoteServiceManager);
		this.remoteContext = (XComponentContext) UnoRuntime.queryInterface(
				XComponentContext.class,
				properties.getPropertyValue("DefaultContext"));

		this.connected = true;
		this.inDisconnect = false;
	}

	public synchronized void disconnect() throws Exception {
		if (this.debug) {
			System.err.print("Disconnecting... ");
		}

		this.inDisconnect = true;

		if (this.connected) {
			this.bridgeComponent.dispose();
		}

		this.connected = false;

		if (this.debug) {
			System.err.print("Done.\n");
		}
	}

	public synchronized void convert(HashMap opts) throws Exception {
		boolean local = true;

		String input_file = (String) opts.get("input_file");
		String output_file = (String) opts.get("output_file");
		String output_type = (String) opts.get("output_type");

		ArrayList input_file_list = new ArrayList();
		if (opts.containsKey("input_file_list")) {
			input_file_list = (ArrayList) opts.get("input_file_list");
		}

		boolean insert_page_break = false;
		if (opts.containsKey("insert_page_break")) {
			insert_page_break = ((Boolean) opts.get("insert_page_break"))
					.booleanValue();
		}

		String input_file_url = input_file;
		String output_file_url = output_file;

		OOoInputStream oooInStream = new OOoInputStream(new byte[0]);
		OOoOutputStream oooOutStream = new OOoOutputStream();

		if (local) {
			File fileIn = new File(input_file);
			File fileOut = new File(output_file);

			Object unoFileIdentifierConverter = this
					.getService("com.sun.star.ucb.FileContentProvider");
			XFileIdentifierConverter fileIdentifierConverter = (XFileIdentifierConverter) UnoRuntime
					.queryInterface(XFileIdentifierConverter.class,
							unoFileIdentifierConverter);

			input_file_url = fileIdentifierConverter.getFileURLFromSystemPath(
					"", fileIn.getAbsolutePath());
			output_file_url = fileIdentifierConverter.getFileURLFromSystemPath(
					"", fileOut.getAbsolutePath());
		} else {
			InputStream fileInStream = new BufferedInputStream(
					new FileInputStream(input_file));
			ByteArrayOutputStream bytes = new ByteArrayOutputStream();
			byte[] byteBuffer = new byte[8192];
			int byteBufferLength = 0;
			while ((byteBufferLength = fileInStream.read(byteBuffer)) > 0) {
				bytes.write(byteBuffer, 0, byteBufferLength);
			}
			fileInStream.close();

			oooInStream = new OOoInputStream(bytes.toByteArray());
			oooOutStream = new OOoOutputStream();
		}

		if (this.debug) {
			System.err.print("Converting '" + input_file + "' to '"
					+ output_file + "' with type '" + output_type + "'\n");
		}

		Object unoDesktop = this.getService("com.sun.star.frame.Desktop");
		XComponentLoader componentLoader = (XComponentLoader) UnoRuntime
				.queryInterface(XComponentLoader.class, unoDesktop);

		XComponent doc;
		if (local) {
			PropertyValue[] properties = new PropertyValue[1];
			properties[0] = new PropertyValue();
			properties[0].Name = "Hidden";
			properties[0].Value = new Boolean(true);

			doc = componentLoader.loadComponentFromURL(input_file_url,
					"_blank", 0, properties);
		} else {
			PropertyValue[] properties = new PropertyValue[2];
			properties[0] = new PropertyValue();
			properties[1] = new PropertyValue();
			properties[0].Name = "InputStream";
			properties[0].Value = oooInStream;
			properties[1].Name = "Hidden";
			properties[1].Value = new Boolean(true);

			doc = componentLoader.loadComponentFromURL("private:stream",
					"_blank", 0, properties);
		}

		String input_type = "";
		XServiceInfo docServiceInfo = (XServiceInfo) UnoRuntime.queryInterface(
				XServiceInfo.class, doc);
		if (docServiceInfo
				.supportsService("com.sun.star.text.GenericTextDocument")) {
			input_type = "writer";
		} else if (docServiceInfo
				.supportsService("com.sun.star.sheet.SpreadsheetDocument")) {
			input_type = "calc";
		} else if (docServiceInfo
				.supportsService("com.sun.star.presentation.PresentationDocument")) {
			input_type = "impress";
		} else if (docServiceInfo
				.supportsService("com.sun.star.presentation.DrawingDocument")) {
			input_type = "draw";
		} else {
			throw new Exception("Could not find document type for '"
					+ input_file + "'");
		}

		String filterName = "";
		if (output_type.equals("pdf") || output_type.equals("pdfa")) {
			filterName = input_type + "_pdf_Export";
		} else if (output_type.equals("html")) {
			if (input_type.equals("writer")) {
				filterName = "HTML (StarWriter)";
			} else if (input_type.equals("calc")) {
				filterName = "HTML (StarCalc)";
			} else if (input_type.equals("impress")) {
				filterName = "impress_html_Export";
			}
		} else if (output_type.equals("odt")) {
			if (input_type.equals("writer")) {
				filterName = "writer8";
			}
		} else if (output_type.equals("doc")) {
			if (input_type.equals("writer")) {
				filterName = "MS Word 97";
			}
		} else if (output_type.equals("rtf")) {
			if (input_type.equals("writer")) {
				filterName = "Rich Text Format";
			}
		} else if (output_type.equals("txt")) {
			if (input_type.equals("writer")) {
				filterName = "Text";
			}
		} else if (output_type.equals("ods")) {
			if (input_type.equals("calc")) {
				filterName = "calc8";
			}
		} else if (output_type.equals("xls")) {
			if (input_type.equals("calc")) {
				filterName = "MS Excel 97";
			}
		} else if (output_type.equals("odp")) {
			if (input_type.equals("impress")) {
				filterName = "impress8";
			}
		} else if (output_type.equals("ppt")) {
			if (input_type.equals("impress")) {
				filterName = "MS PowerPoint 97";
			}
		} else if (output_type.equals("swf")) {
			if (input_type.equals("impress")) {
				filterName = "impress_flash_Export";
			}
		}

		if (filterName.equals("")) {
			throw new Exception(
					"Could not find a valid output filter for converting '"
							+ input_file + "' to '" + output_type + "'");
		}

		if (input_type.equals("writer")) {
			if (local && input_file_list.size() > 0) {
				this.insertDocuments(doc, input_file_list, insert_page_break);
			}
			this.updateIndexes(doc);
		}

		if (output_type.equals("odt")) {
			this.embedImages(doc);
		}

		/*
		 * Set properties for conversion
		 */
		ArrayList propertyList = new ArrayList();
		PropertyValue property;

		property = new PropertyValue();
		property.Name = "FilterName";
		property.Value = filterName;
		propertyList.add(property);

		if (!local) {
			property = new PropertyValue();
			property.Name = "OutputStream";
			property.Value = oooOutStream;
			propertyList.add(property);
		}

		if (output_type.equals("pdfa")) {
			PropertyValue[] pdfFilterData = new PropertyValue[2];

			pdfFilterData[0] = new PropertyValue();
			pdfFilterData[0].Name = "UseLossLessCompression";
			pdfFilterData[0].Value = new Boolean(true);

			pdfFilterData[1] = new PropertyValue();
			pdfFilterData[1].Name = "SelectPdfVersion";
			pdfFilterData[1].Value = new Integer(1);

			property = new PropertyValue();
			property.Name = "FilterData";
			property.Value = pdfFilterData;
			propertyList.add(property);
		}

		PropertyValue[] properties = new PropertyValue[propertyList.size()];
		for (int i = 0; i < propertyList.size(); i++) {
			properties[i] = (PropertyValue) propertyList.get(i);
		}

		/*
		 * Convert the document
		 */

		XStorable storable = (XStorable) UnoRuntime.queryInterface(
				XStorable.class, doc);
		if (local) {
			storable.storeToURL(output_file_url, properties);
		} else {
			storable.storeToURL("private:stream", properties);
		}

		XCloseable closeableDoc = (XCloseable) UnoRuntime.queryInterface(
				XCloseable.class, doc);
		closeableDoc.close(true);

		if (!local) {
			FileOutputStream fileOutStream = new FileOutputStream(output_file);
			fileOutStream.write(oooOutStream.toByteArray());
			fileOutStream.close();
		}
	}

	public void updateIndexes(XComponent doc) throws Exception {
		XRefreshable refreshableDoc = (XRefreshable) UnoRuntime.queryInterface(
				XRefreshable.class, doc);
		if (refreshableDoc == null) {
			return;
		}

		refreshableDoc.refresh();

		Object unoDispatchHelper = this
				.getService("com.sun.star.frame.DispatchHelper");
		XDispatchHelper dispatchHelper = (XDispatchHelper) UnoRuntime
				.queryInterface(XDispatchHelper.class, unoDispatchHelper);

		XModel documentModel = (XModel) UnoRuntime.queryInterface(XModel.class,
				doc);
		XController documentController = documentModel.getCurrentController();
		XFrame frame = documentController.getFrame();
		XDispatchProvider dispatchProvider = (XDispatchProvider) UnoRuntime
				.queryInterface(XDispatchProvider.class, frame);

		PropertyValue[] properties = new PropertyValue[0];
		dispatchHelper.executeDispatch((XDispatchProvider) dispatchProvider,
				".uno:UpdateAllIndexes", "", 0, properties);
	}

	public void embedImages(XComponent doc) throws Exception {
		Object graph;
		PropertyValue[] mediaProperties = new PropertyValue[1];

		XTextGraphicObjectsSupplier graphObjSupplier = (XTextGraphicObjectsSupplier) UnoRuntime
				.queryInterface(XTextGraphicObjectsSupplier.class, doc);
		XNameAccess nameAccess = graphObjSupplier.getGraphicObjects();
		String[] imageList = nameAccess.getElementNames();

		Object unoGraphicProvider = this
				.getService("com.sun.star.graphic.GraphicProvider");
		XGraphicProvider graphicProvider = (XGraphicProvider) UnoRuntime
				.queryInterface(XGraphicProvider.class, unoGraphicProvider);

		for (int i = 0; i < imageList.length; i++) {
			graph = nameAccess.getByName(imageList[i]);
			XPropertySet propertySet = (XPropertySet) UnoRuntime
					.queryInterface(XPropertySet.class, graph);
			mediaProperties[0] = new PropertyValue();
			mediaProperties[0].Name = "URL";
			mediaProperties[0].Value = propertySet.getPropertyValue(
					"GraphicURL").toString();
			if (this.debug) {
				System.err.print("Embeding image '" + mediaProperties[0].Value
						+ "'\n");
			}
			propertySet.setPropertyValue("Graphic",
					graphicProvider.queryGraphic(mediaProperties));
		}
	}

	public void insertDocuments(XComponent doc, ArrayList fileList,
			boolean insertPageBreak) throws Exception {
		XTextDocument textDocument = (XTextDocument) UnoRuntime.queryInterface(
				XTextDocument.class, doc);
		XSimpleText text = (XSimpleText) textDocument.getText();
		XTextCursor cursor = text.createTextCursor();
		XDocumentInsertable cursorInsert = (XDocumentInsertable) UnoRuntime
				.queryInterface(XDocumentInsertable.class, cursor);
		XPropertySet cursorProperties = (XPropertySet) UnoRuntime
				.queryInterface(XPropertySet.class, cursor);

		String fileUrl;
		File file;

		Object unoFileIdentifierConverter = this
				.getService("com.sun.star.ucb.FileContentProvider");
		XFileIdentifierConverter fileIdentifierConverter = (XFileIdentifierConverter) UnoRuntime
				.queryInterface(XFileIdentifierConverter.class,
						unoFileIdentifierConverter);

		for (int i = 0; i < fileList.size(); i++) {
			file = new File((String) fileList.get(i));
			fileUrl = fileIdentifierConverter.getFileURLFromSystemPath("",
					file.getAbsolutePath());
			cursor.gotoEnd(false);
			if (insertPageBreak) {
				cursorProperties.setPropertyValue("BreakType",
						BreakType.PAGE_BEFORE);
			}
			cursorInsert.insertDocumentFromURL(fileUrl, null);
		}
	}

	private Object getService(String className) throws Exception {
		try {
			if (!this.connected) {
				this.connect();
			}
			return this.remoteServiceManager.createInstanceWithContext(
					className, this.remoteContext);
		} catch (Exception exception) {
			throw new Exception("Could not get service '" + className + "'");
		}
	}

	public void disposing(EventObject arg0) {
		this.connected = false;
	}

	public void notifyEvent(com.sun.star.document.EventObject arg0) {
		return;
	}
}