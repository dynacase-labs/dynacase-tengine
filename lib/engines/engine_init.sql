INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'text', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/vnd.oasis.opendocument.text', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/vnd.oasis.opendocument.presentation', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/vnd.oasis.opendocument.spreadsheet', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/vnd.sun.xml.writer', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/vnd.sun.xml.calc', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/vnd.sun.xml.impress', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/vnd.ms-powerpoint', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/vnd.ms-excel', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/msword', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/pdf', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'text/html', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'text/x-mail', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/vnd.ms-outlook', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/mbox', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/x-iwork-numbers-sffnumbers', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/x-iwork-pages-sffpages', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/x-iWork-keynote-sffkey', '@TE_HOME@/lib/engines/tika2txt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('utf8', 'application/epub+zip', '@TE_HOME@/lib/engines/tika2txt', NULL);

INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'application/vnd.oasis.opendocument.text', '@TE_HOME@/lib/engines/ooo2pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'application/vnd.oasis.opendocument.presentation', '@TE_HOME@/lib/engines/ooo2pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'application/vnd.oasis.opendocument.spreadsheet', '@TE_HOME@/lib/engines/ooo2pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'application/vnd.ms-excel', '@TE_HOME@/lib/engines/ooo2pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'application/msword', '@TE_HOME@/lib/engines/ooo2pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'application/vnd.ms-powerpoint', '@TE_HOME@/lib/engines/ooo2pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'application/vnd.sun.xml.writer', '@TE_HOME@/lib/engines/ooo2pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'application/vnd.sun.xml.calc', '@TE_HOME@/lib/engines/ooo2pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'application/vnd.sun.xml.impress', '@TE_HOME@/lib/engines/ooo2pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'application/pdf', '/bin/cp', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'text/html', '@TE_HOME@/lib/engines/html2pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'text', '@TE_HOME@/lib/engines/txt2pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '@TE_HOME@/lib/engines/ooo2pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '@TE_HOME@/lib/engines/ooo2pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdf', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', '@TE_HOME@/lib/engines/ooo2pdf', NULL);

INSERT INTO engine (name, mime, command, "comment") VALUES ('thumbnail', 'application/vnd.oasis.opendocument.text', '@TE_HOME@/lib/engines/ooo2thumb', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('thumbnail', 'application/vnd.oasis.opendocument.presentation', '@TE_HOME@/lib/engines/ooo2thumb', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('thumbnail', 'application/vnd.oasis.opendocument.spreadsheet', '@TE_HOME@/lib/engines/ooo2thumb', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('thumbnail', 'application/vnd.oasis.opendocument.graphics', '@TE_HOME@/lib/engines/ooo2thumb', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('thumbnail', 'image', '@TE_HOME@/lib/engines/img2thumb', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('thumbnail', 'application/pdf', '@TE_HOME@/lib/engines/img2thumb', NULL);


INSERT INTO engine (name, mime, command, "comment") VALUES ('odt', 'text/html', '@TE_HOME@/lib/engines/html2odt', NULL);

INSERT INTO engine (name, mime, command, "comment") VALUES ('pdfa', 'application/vnd.oasis.opendocument.text', '@TE_HOME@/lib/engines/ooo2pdfa', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdfa', 'application/vnd.oasis.opendocument.presentation', '@TE_HOME@/lib/engines/ooo2pdfa', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdfa', 'application/vnd.oasis.opendocument.spreadsheet', '@TE_HOME@/lib/engines/ooo2pdfa', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdfa', 'application/msword', '@TE_HOME@/lib/engines/ooo2pdfa', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdfa', 'application/vnd.ms-excel', '@TE_HOME@/lib/engines/ooo2pdfa', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdfa', 'application/vnd.ms-powerpoint', '@TE_HOME@/lib/engines/ooo2pdfa', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdfa', 'application/vnd.sun.xml.writer', '@TE_HOME@/lib/engines/ooo2pdfa', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdfa', 'application/vnd.sun.xml.calc', '@TE_HOME@/lib/engines/ooo2pdfa', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdfa', 'application/vnd.sun.xml.impress', '@TE_HOME@/lib/engines/ooo2pdfa', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdfa', 'text/html', '@TE_HOME@/lib/engines/html2pdfa', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdfa', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '@TE_HOME@/lib/engines/ooo2pdfa', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdfa', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '@TE_HOME@/lib/engines/ooo2pdfa', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('pdfa', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', '@TE_HOME@/lib/engines/ooo2pdfa', NULL);

INSERT INTO engine (name, mime, command, "comment") VALUES ('mergeodt', 'application/zip', '@TE_HOME@/lib/engines/zip2star odt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('mergepdf', 'application/zip', '@TE_HOME@/lib/engines/zip2star pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('mergepdfa', 'application/zip', '@TE_HOME@/lib/engines/zip2star pdfa', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('mergeodt', 'application/x-zip', '@TE_HOME@/lib/engines/zip2star odt', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('mergepdf', 'application/x-zip', '@TE_HOME@/lib/engines/zip2star pdf', NULL);
INSERT INTO engine (name, mime, command, "comment") VALUES ('mergepdfa', 'application/x-zip', '@TE_HOME@/lib/engines/zip2star pdfa', NULL);

INSERT INTO engine (name, mime, command, "comment") VALUES ('doc', 'application/vnd.oasis.opendocument.text', '@TE_HOME@/lib/engines/ooo2doc', NULL);
