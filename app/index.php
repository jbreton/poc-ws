<html>
    <head>
        <title>WebSocket TEST</title>
    </head>
    <body>
        <h1>Test</h1>
        <script>
			function WebSocketClient(url) {
				this.init(url);
			}
			
			WebSocketClient.prototype = {
				init : function(url) {
					this.listeners = {};
					
					this.onCloseListerners = [];
					
					this.url = url;
					this.socket = new WebSocket(url);
					
					var self = this;
					this.socket.onmessage = function(message) {
						try {
							var json = JSON.parse(message.data);
							if(self.listeners[json.eventType]) {
								for(var index in self.listeners[json.eventType]) {
									self.listeners[json.eventType][index].call({}, json.data);
								}
							}
						}
						catch(exception) {
							console.debug(exception);
						}
					};
				},
				on : function(eventType, handler) {
					if(typeof handler == 'function') {
						if(!this.listeners[eventType]) {
							this.listeners[eventType] = [];
						}

						if(this.listeners[eventType].indexOf(handler) < 0) {
							this.listeners[eventType].push(handler);
							return true;
						}
						else {
							return false;
						}
					}
				},
				off : function(eventType, handler) {
					if(this.listeners[eventType]) {
						var index = this.listeners[eventType].indexOf(handler);
						if(index > -1) {
							this.listeners[eventType].splice(index);
							return true;
						}
					}
					
					return false;
				},
				send : function(eventType, data) {
					this.socket.send(JSON.stringify({eventType:eventType,data:data}));
				},
				bindOnClose : function(handler) {
					if(typeof handler == 'function') {
						var index = this.onCloseListeners.indexOf(handler);
						if(index < 0) {
							this.onCloseListeners.push(index);
							return true;
						}
						else {
							return false;
						}
					}
				},
				unbindOnClose : function(handler) {
					var index = this.onCloseListeners.indexOf(handler);
					if(index > -1) {
						this.onCloseListeners.splice(index);
						return true;
					}
					else {
						return false;
					}
				}
			}
			
			socket = new WebSocketClient('ws://10.100.1.2:12345');
			socket.on('test', function(data) {
				console.debug(data);
			});
        </script>
    </body>
</html>
