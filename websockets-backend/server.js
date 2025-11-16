import { WebSocketServer, WebSocket } from "ws";
import * as http from "http";
import * as url from "url";
import { uuidv4, broadcast } from "./functions.js";

const HTTP_PORT = 3000;
const PULL_URL = process.env.SYMFONY_PULL_URL;

const clients = {};

const server = http.createServer((req, res) => {
  const reqUrl = url.parse(req.url, true);

  if (reqUrl.pathname === "/pull" && req.method === "POST") {
    let body = "";

    req.on("data", (chunk) => {
      body += chunk.toString();
    });

    req.on("end", () => {
      let data;
      try {
        data = JSON.parse(body);
      } catch (e) {
        res.writeHead(400, { "Content-Type": "application/json" });
        return res.end(
          JSON.stringify({ status: "error", message: "Invalid JSON body" }),
        );
      }

      const { client_id, payload } = data;

      if (!client_id || !payload) {
        res.writeHead(400, { "Content-Type": "application/json" });
        return res.end(
          JSON.stringify({
            status: "error",
            message: "Missing client_id or payload",
          }),
        );
      }

      const clientWs = clients[client_id];

      if (clientWs && clientWs.readyState === WebSocket.OPEN) {
        const messageToSend = JSON.stringify({
          type: "pulled_data",
          source: "HTTP_API",
          data: payload,
        });

        clientWs.send(messageToSend);

        res.writeHead(200, { "Content-Type": "application/json" });
        res.end(
          JSON.stringify({
            status: "success",
            message: `Data sent to client ${client_id}`,
          }),
        );
      } else {
        res.writeHead(404, { "Content-Type": "application/json" });
        res.end(
          JSON.stringify({
            status: "error",
            message: `Client with ID ${client_id} not found or not open`,
          }),
        );
      }
    });
  } else {
    res.writeHead(404);
    res.end("Not Found");
  }
});

const wss = new WebSocketServer({ server });

server.listen(HTTP_PORT, () => {
  console.log(`HTTP Server is running on http://localhost:${HTTP_PORT}`);
  console.log(`WebSocket Server attached and listening for connections.`);
});

console.log(
  `WebSocket Server is running on ws://localhost:${HTTP_PORT}. Awaiting JSON messages.`,
);

wss.on("connection", function connection(ws) {
  const clientId = uuidv4();
  clients[clientId] = ws;

  console.log(
    `New client connected! ID: ${clientId}. Active clients: ${Object.keys(clients).length}`,
  );

  ws.send(
    JSON.stringify({
      type: "connect_info",
      id: clientId,
      message: "Welcome! You are connected to the server.",
    }),
  );

  ws.on("message", function incoming(message) {
    let parsedMessage;

    try {
      parsedMessage = JSON.parse(message.toString("utf8"));
      console.log(
        `Message received from ${clientId}: ${JSON.stringify(parsedMessage)}`,
      );
    } catch (e) {
      console.error(
        `JSON parsing error from ${clientId}: ${message.toString("utf8")}`,
      );
      ws.send(
        JSON.stringify({
          type: "error",
          message: "Invalid message format. JSON is expected.",
        }),
      );
      return;
    }

    if (parsedMessage.action) {
      if (!PULL_URL) {
        console.error("PULL_URL is not defined. Cannot send request.");
        ws.send(
          JSON.stringify({
            type: "error",
            message: "Server configuration error.",
          }),
        );
        return;
      }

      console.log(
        `Action 'getHashes' received. Sending request via fetch to: ${PULL_URL}`,
      );

      fetch(PULL_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          client_id: clientId,
          action: parsedMessage.action,
        }),
      })
        .then((response) => {
          if (response.ok) {
            console.log(
              `Symfony responded (Status ${response.status}). Job dispatched.`,
            );

            ws.send(
              JSON.stringify({
                type: "job_dispatched",
                message: "Hash generation job started asynchronously.",
                clientId: clientId,
              }),
            );
          } else {
            console.error(
              `Error sending request to Symfony: HTTP Status ${response.status}`,
            );
            ws.send(
              JSON.stringify({
                type: "error",
                message: `Failed to start the job (HTTP Error ${response.status}).`,
              }),
            );
          }
        })
        .catch((error) => {
          console.error(
            `Network error when sending request to Symfony: ${error.message}`,
          );
          ws.send(
            JSON.stringify({
              type: "error",
              message: "Failed to connect to the job dispatcher.",
              details: error.message,
            }),
          );
        });

      return;
    }

    const broadcastData = {
      type: "user_message",
      senderId: clientId,
      content: parsedMessage.content || "Empty message",
      timestamp: new Date().toISOString(),
    };

    broadcast(wss.clients, broadcastData);
  });

  ws.on("close", () => {
    console.log(`Client ${clientId} disconnected.`);
    delete clients[clientId];
    console.log(`Active clients: ${Object.keys(clients).length}`);

    broadcast(wss.clients, {
      type: "status_update",
      message: `User ${clientId.substring(0, 8)}... has disconnected.`,
    });
  });

  ws.on("error", (error) => {
    console.error(`An error occurred with client ${clientId}:`, error.message);
  });
});
