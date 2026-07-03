import { createHash } from 'node:crypto';
import { createServer } from 'node:net';

const port = Number(process.env.LPWORK_DEBUGBAR_WS_PORT || 8081);
const host = process.env.LPWORK_DEBUGBAR_WS_HOST || '0.0.0.0';
const guid = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

const server = createServer((socket) => {
    let handshaken = false;

    socket.on('data', (chunk) => {
        if (!handshaken) {
            const request = chunk.toString('utf8');
            const match = request.match(/Sec-WebSocket-Key:\s*(.+)\r\n/i);

            if (!match) {
                socket.destroy();
                return;
            }

            const accept = createHash('sha1')
                .update(match[1].trim() + guid)
                .digest('base64');
            socket.write(
                [
                    'HTTP/1.1 101 Switching Protocols',
                    'Upgrade: websocket',
                    'Connection: Upgrade',
                    `Sec-WebSocket-Accept: ${accept}`,
                    '\r\n',
                ].join('\r\n'),
            );
            handshaken = true;

            return;
        }

        const message = decodeFrame(chunk);

        if (message !== null) {
            socket.write(encodeFrame(`echo: ${message}`));
        }
    });
});

server.on('error', (error) => {
    if (error.code === 'EADDRINUSE') {
        console.error(
            `Port ${port} is already in use. Stop the existing websocket server or set LPWORK_DEBUGBAR_WS_PORT.`,
        );
        process.exit(1);
    }

    throw error;
});

server.listen(port, host, () => {
    console.log(`LPWork debugbar websocket echo server listening on ws://${host}:${port}`);
});

function decodeFrame(buffer) {
    if (buffer.length < 6) {
        return null;
    }

    const opcode = buffer[0] & 0x0f;

    if (opcode === 8) {
        return null;
    }

    let length = buffer[1] & 0x7f;
    let offset = 2;

    if (length === 126) {
        length = buffer.readUInt16BE(offset);
        offset += 2;
    }

    const mask = buffer.subarray(offset, offset + 4);
    offset += 4;
    const payload = buffer.subarray(offset, offset + length);

    return Buffer.from(payload.map((byte, index) => byte ^ mask[index % 4])).toString('utf8');
}

function encodeFrame(message) {
    const payload = Buffer.from(message);
    const header =
        payload.length < 126
            ? Buffer.from([0x81, payload.length])
            : Buffer.from([0x81, 126, payload.length >> 8, payload.length & 0xff]);

    return Buffer.concat([header, payload]);
}
