package com.anakeen.oooserver;

import java.io.File;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashMap;

public class OOoServerCli {

	public static void usage() {
		System.err.print("Usage:\n");
		System.err
				.print("  convert -i <input_file> -o <output_file> [-h <ooo_host>] [-p <ooo_port>] [-t <pdf|pdfa|html|doc>]\n");
		System.err
				.print("  merge -o <output_file> [-h <ooo_host>] [-p <ooo_port] -i <main_input_file> [<input files>]\n");
		return;
	}

	public static void main(String[] args) throws Exception {
		ArrayList argList = new ArrayList(Arrays.asList(args));
		String operation = "";

		int i = 0;

		if (args.length < 1) {
			usage();
			System.exit(1);
		}
		operation = (String) argList.remove(0);
		i++;

		if (operation.equals("convert")) {
			convert(argList);
		} else if (operation.equals("merge")) {
			merge(argList);
		} else {
			System.err.print("Unsupported operation '" + operation + "'\n");
			usage();
			System.exit(1);
		}

		System.exit(0);
	}

	public static void convert(ArrayList argList) throws Exception {
		HashMap opts = new HashMap();

		opts.put("input_file", "");
		opts.put("output_file", "");
		opts.put("ooo_host", "127.0.0.1");
		opts.put("ooo_port", "8123");
		opts.put("output_type", "pdf");
		opts.put("debug", new Boolean(false));

		try {
			while (argList.size() > 0) {
				String opt = (String) argList.remove(0);
				if (opt.equals("-d") || opt.equals("--debug")) {
					opts.put("debug", new Boolean(true));
				} else if (opt.equals("-i")) {
					opts.put("input_file", (String) argList.remove(0));
				} else if (opt.equals("-o")) {
					opts.put("output_file", (String) argList.remove(0));
				} else if (opt.equals("-h")) {
					opts.put("ooo_host", (String) argList.remove(0));
				} else if (opt.equals("-p")) {
					opts.put("ooo_port", (String) argList.remove(0));
				} else if (opt.equals("-t")) {
					opts.put("output_type", (String) argList.remove(0));
				} else {
					throw new Exception("Unknown arg '" + opt + "'");
				}
			}
		} catch (Exception e) {
			e.printStackTrace();
			System.err.print("Error parsing arguments: " + e.getMessage()
					+ "\n");
			usage();
			System.exit(1);
		}

		if (((Boolean) opts.get("debug")).booleanValue()) {
			System.err
					.print("intput_file = '" + opts.get("input_file") + "'\n");
			System.err.print("output_file = '" + opts.get("output_file")
					+ "'\n");
			System.err.print("ooo_host    = '" + opts.get("ooo_host") + "'\n");
			System.err.print("ooo_prot    = '" + opts.get("ooo_port") + "'\n");
			System.err.print("output_type = '" + opts.get("output_type")
					+ "'\n");
		}

		if (opts.get("input_file").equals("")
				|| opts.get("output_file").equals("")) {
			System.err.print("Missing input_file or output_file.\n");
			usage();
			System.exit(1);
		}

		File intputFile = new File((String) opts.get("input_file"));
		if (!intputFile.exists()) {
			System.err.print("Input file '" + opts.get("input_file")
					+ "' does not exists.\n");
			System.exit(1);
		}

		OOoServer OOO = new OOoServer((String) opts.get("ooo_host"),
				(String) opts.get("ooo_port"));
		OOO.debug = ((Boolean) opts.get("debug")).booleanValue();

		try {
			OOO.connect();
		} catch (Exception e) {
			e.printStackTrace();
			System.err.print("Error connecting to '" + opts.get("ooo_host")
					+ ":" + opts.get("ooo_port") + "': " + e.getMessage()
					+ "\n");
			System.exit(2);
		}

		try {
			OOO.convert(opts);
		} catch (Exception e) {
			e.printStackTrace();
			System.err.println("Error converting '" + opts.get("input_file")
					+ "' to '" + opts.get("output_file") + "' with type '"
					+ opts.get("output_type") + "': " + e.getMessage());
			OOO.disconnect();
			System.exit(3);
		}

		OOO.disconnect();
		return;
	}

	public static void merge(ArrayList argList) throws Exception {
		HashMap opts = new HashMap();

		opts.put("input_file", "");
		opts.put("output_file", "");
		opts.put("ooo_host", "127.0.0.1");
		opts.put("ooo_port", "8123");
		opts.put("output_type", "pdf");
		opts.put("debug", new Boolean(false));
		opts.put("insert_page_break", new Boolean(false));
		ArrayList input_file_list = new ArrayList();

		try {
			while (argList.size() > 0) {
				String opt = (String) argList.remove(0);
				if (opt.equals("-d") || opt.equals("--debug")) {
					opts.put("debug", new Boolean(true));
				} else if (opt.equals("-i")) {
					opts.put("input_file", (String) argList.remove(0));
				} else if (opt.equals("-o")) {
					opts.put("output_file", (String) argList.remove(0));
				} else if (opt.equals("-h")) {
					opts.put("ooo_host", (String) argList.remove(0));
				} else if (opt.equals("-p")) {
					opts.put("ooo_port", (String) argList.remove(0));
				} else if (opt.equals("-t")) {
					opts.put("output_type", (String) argList.remove(0));
				} else if (opt.equals("-b")) {
					opts.put("insert_page_break", new Boolean(true));
					;
				} else {
					input_file_list.add(opt);
				}
			}
		} catch (Exception e) {
			e.printStackTrace();
			System.err.print("Error parsing arguments: " + e.getMessage()
					+ "\n");
			usage();
			System.exit(1);
		}
		opts.put("input_file_list", input_file_list);

		if (((Boolean) opts.get("debug")).booleanValue()) {
			System.err.print("intput_file       = '" + opts.get("input_file")
					+ "'\n");
			System.err.print("output_file       = '" + opts.get("output_file")
					+ "'\n");
			System.err.print("ooo_host          = '" + opts.get("ooo_host")
					+ "'\n");
			System.err.print("ooo_prot          = '" + opts.get("ooo_port")
					+ "'\n");
			System.err.print("output_type       = '" + opts.get("output_type")
					+ "'\n");
			System.err.print("insert_page_break = '"
					+ opts.get("insert_page_break") + "'\n");
			System.err.print("other input_file  = (\n");
			for (int i = 0; i < input_file_list.size(); i++) {
				System.err.print("    '" + (String) input_file_list.get(0)
						+ "'\n");
			}
			System.err.print("    )\n");
		}

		if (opts.get("input_file").equals("")
				|| opts.get("output_file").equals("")) {
			System.err.print("Missing input_file or output_file.\n");
			usage();
			System.exit(1);
		}

		File intputFile = new File((String) opts.get("input_file"));
		if (!intputFile.exists()) {
			System.err.print("Input file '" + opts.get("input_file")
					+ "' does not exists.\n");
			System.exit(1);
		}

		OOoServer OOO = new OOoServer((String) opts.get("ooo_host"),
				(String) opts.get("ooo_port"));
		OOO.debug = ((Boolean) opts.get("debug")).booleanValue();

		try {
			OOO.connect();
		} catch (Exception e) {
			e.printStackTrace();
			System.err.print("Error connecting to '" + opts.get("ooo_host")
					+ ":" + opts.get("ooo_port") + "': " + e.getMessage()
					+ "\n");
			System.exit(2);
		}

		try {
			OOO.convert(opts);
		} catch (Exception e) {
			e.printStackTrace();
			System.err.println("Error converting '" + opts.get("input_file")
					+ "' to '" + opts.get("output_file") + "' with type '"
					+ opts.get("output_type") + "': " + e.getMessage());
			OOO.disconnect();
			System.exit(3);
		}

		OOO.disconnect();
		return;
	}
}