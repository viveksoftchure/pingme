<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Random Chat</title>
    <style>
body {
    font-family: Arial, sans-serif;
    height: 100vh;
    margin: 0;
}
body * {
    box-sizing: border-box;
}
.chat-container {
    width: 100%;
    height: 100vh;
    min-width: 400px;
    background-color: #f4f4f4;
    padding: 15px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}
#chat-box {
    padding: 15px;
    background: #fff;
    border-radius: 5px;
    margin-bottom: 10px;
    overflow-y: scroll;
}
#chat-box .message {
    margin-bottom: 5px;
}
#chat-box .message.start-chat {
    color: #989897;
    font-style: italic;
}
#chat-box .message.system {
    font-size: 14px;
    margin-top: 5px;
    text-align: left;
    color: #888;
    font-style: italic;
}
#chat-box .message.you strong {
    color: #1900ff;
}
#chat-box .message.stranger strong {
    color: #ff0604;
}

.input-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
#message {
    width: calc(100% - 60px);
    width: 100%;
    padding: 10px;
    margin: 0 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
}
.btn {
    padding: 10px 15px;
    border: none;
    background-color: #0d6efd;
    color: white;
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 400;
}
.btn:disabled {
    background-color: #ccc;
    cursor: unset;
}
    </style>
</head>
<body>

    <div class="chat-container">
        <!-- Messages -->
        <div id="chat-box">

        </div>

        <!-- Input Area -->
        <div class="input-container">
            <button id="disconnect" class="btn btn-primary">Disconnect</button>
            <input type="text" id="message" class="form-control me-2" placeholder="Type a message...">
            <button id="send" disabled="disabled" class="btn btn-primary">Send</button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const chatBox = $('#chat-box');
            const messageInput = $('#message');
            const sendBtn = $('#send');
            const disconnectBtn = $('#disconnect');
            const typing = '<div class="message typing system">Stranger is typing...</div>';

            // Connect to WebSocket server
            var socket = new WebSocket('ws://localhost:8080');
            var userName = 'User_' + Math.floor(Math.random() * 1000); // Random user name
            let socketClosed = false;

            console.log('socketClosed initial:- ' + socketClosed);

            // When the WebSocket connection is opened
            socket.onopen = function() {
                console.log("Connected to WebSocket server.");

                // chatBox.append('<p><i>Connected as ' + userName + '</i></p>');
                chatBox.append('<div class="message start-chat">You\'re talking to a random person. Say hi!</div>');

                // Enable the send and disconnect buttons
                sendBtn.prop('disabled', false);
                disconnectBtn.prop('disabled', false);
            };

            // When the WebSocket connection is closed
            socket.onclose = function () {
                console.log("Disonnected to WebSocket server.");

                // chatBox.append('<div class="message system text-muted">Connection closed by the server or user.</div>');
                // chatBox.scrollTop(chatBox[0].scrollHeight);

                // Disable the send and disconnect buttons
                sendBtn.prop('disabled', true);
                disconnectBtn.prop('disabled', true);
                socketClosed = true;
                console.log('socketClosed on close:- ' + socketClosed);
            };

            // When the WebSocket connection error
            socket.onerror = function (error) {
                console.error('WebSocket error:', error);
                // chatBox.append('<div class="message system text-danger">An error occurred. Please try reconnecting.</div>');
                // chatBox.scrollTop(chatBox[0].scrollHeight);
            };

            // When a message is received from the server
            socket.onmessage = function (event) {
                console.log("message recieved");
                const data = JSON.parse(event.data);

                // Handle typing notification
                if (data.type === 'typing') {
                    sendBtn.prop('disabled', false);
                    disconnectBtn.prop('disabled', false);

                    chatBox.find('.typing').remove();
                    chatBox.append(typing);
                    clearTimeout(window.typingTimeout);
                    window.typingTimeout = setTimeout(() => {
                        chatBox.find('.typing').remove();
                    }, 5000); // Hide after 5 second of inactivity
                }

                // Handle new messages
                if (data.type === 'message') {
                    chatBox.find('.typing').remove();
                    var message = data.message;
                    chatBox.append('<div class="message stranger"><strong>Stranger:</strong> ' + message + '</div>');
                    chatBox.scrollTop(chatBox[0].scrollHeight); // Scroll to the bottom
                }

                // Handle typing notification
                if (data.status === 'closed') {
                    sendBtn.prop('disabled', true);
                    disconnectBtn.prop('disabled', true);
                    socketClosed = true;
                    console.log('socketClosed on message:- ' + socketClosed);
                }
            };

            // Notify when the user is typing
            messageInput.on('input', function () {
                if (socket.readyState === WebSocket.OPEN) {
                    socket.send(JSON.stringify({ type: 'typing' }));
                }
            });

            // Listen for keypress events in the input field
            messageInput.keypress(function (e) {
                if (e.which === 13) { // 13 is the key code for the Enter key
                    e.preventDefault(); // Prevent the default action (form submission or new line)
                    sendBtn.click(); // Trigger the send button click event
                }
            });

            // Send message to the WebSocket server
            sendBtn.click(function() {
                var message = messageInput.val();

                console.log('socketClosed on send:- ' + socketClosed);
                if (!socketClosed && socket.readyState === WebSocket.OPEN) {
                    // Send message through WebSocket
                    socket.send(JSON.stringify({ type: 'message', message }));

                    chatBox.append('<div class="message you"><strong>You:</strong> ' + message + '</div>');
                    chatBox.scrollTop(chatBox[0].scrollHeight); // Scroll to the bottom

                    // Clear the input field
                    messageInput.val('');
                }
            });

            // Disconnect from WebSocket
            disconnectBtn.on('click', function () {
                console.log("Disonnecte clicked");
                if (socket.readyState === WebSocket.OPEN || socket.readyState === WebSocket.CONNECTING) {
                    socket.close();
                    chatBox.append('<div class="message system">You have disconnected.</div>');
                    chatBox.scrollTop(chatBox[0].scrollHeight);
                    sendBtn.prop('disabled', true);
                }
            });
        });
    </script>
</body>
</html>
