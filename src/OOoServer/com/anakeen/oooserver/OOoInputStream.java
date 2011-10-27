package com.anakeen.oooserver;

import java.io.ByteArrayInputStream;

import com.sun.star.io.BufferSizeExceededException;
import com.sun.star.io.NotConnectedException;
import com.sun.star.io.XInputStream;
import com.sun.star.io.XSeekable;

/**
 * <a href="http://www.oooforum.org/forum/viewtopic.phtml?t=13205">OOInputStream
 * from the thread <b>OOo-Java: Using XInputStream...</b></a>
 */
public class OOoInputStream extends ByteArrayInputStream implements
		XInputStream, XSeekable {

	public OOoInputStream(byte[] buf) {
		super(buf);
	}

	//
	// Implement XInputStream
	//

	public int readBytes(byte[][] buffer, int bufferSize)
			throws NotConnectedException, BufferSizeExceededException,
			com.sun.star.io.IOException {
		int numberOfReadBytes;
		try {
			byte[] bytes = new byte[bufferSize];
			numberOfReadBytes = super.read(bytes);
			if (numberOfReadBytes > 0) {
				if (numberOfReadBytes < bufferSize) {
					byte[] smallerBuffer = new byte[numberOfReadBytes];
					System.arraycopy(bytes, 0, smallerBuffer, 0,
							numberOfReadBytes);
					bytes = smallerBuffer;
				}
			} else {
				bytes = new byte[0];
				numberOfReadBytes = 0;
			}

			buffer[0] = bytes;
			return numberOfReadBytes;
		} catch (java.io.IOException e) {
			throw new com.sun.star.io.IOException(e.getMessage(), this);
		}
	}

	public int readSomeBytes(byte[][] buffer, int bufferSize)
			throws NotConnectedException, BufferSizeExceededException,
			com.sun.star.io.IOException {
		return readBytes(buffer, bufferSize);
	}

	public void skipBytes(int skipLength) throws NotConnectedException,
			BufferSizeExceededException, com.sun.star.io.IOException {
		skip(skipLength);
	}

	public void closeInput() throws NotConnectedException,
			com.sun.star.io.IOException {
		try {
			close();
		} catch (java.io.IOException e) {
			throw new com.sun.star.io.IOException(e.getMessage(), this);
		}
	}

	//
	// Implement XSeekable
	//

	public long getLength() throws com.sun.star.io.IOException {
		return count;
	}

	public long getPosition() throws com.sun.star.io.IOException {
		return pos;
	}

	public void seek(long position) throws IllegalArgumentException,
			com.sun.star.io.IOException {
		pos = (int) position;
	}
}