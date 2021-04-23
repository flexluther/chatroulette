/*jshint esversion: 6 */
/*jshint node: true */
"use strict";

const express = require('express');
const app = express();
const http = require('http');
const config = require('./config');
const server = http.createServer(app);
const io = require('socket.io').listen(server);
const listner = server.listen(config.port, function() {
	console.log('Listening on', listner.address().port);
});

require('./socket')(io);
