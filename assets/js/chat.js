(function () {
    const app = document.querySelector("[data-chat-app]");

    if (!app || !window.ChatHttp) {
        return;
    }

    const currentUserId = Number(app.dataset.currentUserId || 0);
    let activeChatType = app.dataset.selectedChatType || "direct";
    let activeTargetId = Number(app.dataset.selectedTargetId || 0);
    const chatPage = app.dataset.chatPage;
    const usersEndpoint = app.dataset.usersEndpoint;
    const messagesEndpoint = app.dataset.messagesEndpoint;
    const sendEndpoint = app.dataset.sendEndpoint;
    const seenEndpoint = app.dataset.seenEndpoint;

    const rosterElement = document.getElementById("conversationRoster");
    const boardElement = document.getElementById("messagesBoard");
    const headerElement = document.getElementById("chatHeader");
    const formElement = document.getElementById("messageForm");
    const messageInput = document.getElementById("messageInput");
    const mediaInput = document.getElementById("mediaInput");
    const attachLabel = document.querySelector("[data-attach-label]");
    const composerHint = document.getElementById("composerHint");

    const escapeHtml = function (value) {
        const div = document.createElement("div");
        div.textContent = value || "";
        return div.innerHTML;
    };

    const avatarTemplate = function (user, large) {
        if (user.avatar_url) {
            return '<img src="' + escapeHtml(user.avatar_url) + '" alt="' + escapeHtml(user.name) + '" class="avatar-image' + (large ? ' avatar-large' : '') + '">';
        }

        return '<div class="avatar-fallback' + (large ? ' avatar-large' : '') + '">' + escapeHtml(user.initials) + '</div>';
    };

    const scrollMessages = function () {
        if (boardElement) {
            boardElement.scrollTop = boardElement.scrollHeight;
        }
    };

    const setComposerHint = function (message) {
        if (composerHint) {
            composerHint.textContent = message || "";
        }
    };

    const resetComposer = function () {
        if (formElement) {
            formElement.reset();
        }

        if (messageInput) {
            messageInput.value = "";
            messageInput.style.height = "auto";
        }

        if (mediaInput) {
            mediaInput.value = "";
        }

        if (attachLabel) {
            attachLabel.textContent = "Attach image";
        }

        setComposerHint("");
    };

    const renderRoster = function (users) {
        if (!rosterElement) {
            return;
        }

        if (!users.length) {
            rosterElement.innerHTML = '' +
                '<div class="empty-card slim">' +
                '<h3>No contacts available</h3>' +
                '<p>Create one more account first, then return here.</p>' +
                '</div>';
            return;
        }

        rosterElement.innerHTML = users.map(function (user) {
            const unread = Number(user.unread_count || 0);
            const isActive = user.chat_type === activeChatType && Number(user.target_id) === activeTargetId;

            return '' +
                '<button type="button" class="chat-user' + (isActive ? ' is-active' : '') + '" data-chat-type="' + escapeHtml(user.chat_type || "direct") + '" data-chat-target="' + escapeHtml(String(user.target_id)) + '">' +
                    '<div class="contact-identity">' +
                        avatarTemplate(user, false) +
                        '<div class="chat-user-copy">' +
                            '<strong>' + escapeHtml(user.name) + '</strong>' +
                            '<p>' + escapeHtml(user.last_message || "Start a new conversation") + '</p>' +
                        '</div>' +
                    '</div>' +
                    '<div class="chat-user-meta">' +
                        '<span>' + escapeHtml(user.time_label || "New") + '</span>' +
                        (unread > 0 ? '<span class="unread-pill">' + unread + '</span>' : '') +
                    '</div>' +
                '</button>';
        }).join("");
    };

    const renderHeader = function (user) {
        if (!headerElement || !user) {
            return;
        }

        headerElement.innerHTML = '' +
            '<div class="contact-identity">' +
                avatarTemplate(user, true) +
                '<div>' +
                    '<h2>' + escapeHtml(user.name) + '</h2>' +
                    '<p>' + escapeHtml(user.status_text) + '</p>' +
                '</div>' +
            '</div>';
    };

    const renderMessages = function (messages) {
        if (!boardElement) {
            return;
        }

        if (!messages.length) {
            boardElement.innerHTML = '' +
                '<div class="empty-chat" id="emptyChat">' +
                    '<span class="eyebrow">Conversation ready</span>' +
                    '<h3>Send the first message</h3>' +
                    '<p>This thread is connected to your database and will start filling as soon as you send a text or image.</p>' +
                '</div>';
            return;
        }

        boardElement.innerHTML = messages.map(function (message) {
            const media = message.media_url
                ? '<img src="' + escapeHtml(message.media_url) + '" alt="Shared media" class="message-media">'
                : '';
            const text = message.message
                ? '<p>' + escapeHtml(message.message).replace(/\n/g, "<br>") + '</p>'
                : '';

            return '' +
                '<article class="message-row' + (message.is_mine ? ' is-mine' : '') + '">' +
                    '<div class="message-bubble">' +
                        media +
                        text +
                        '<span>' + escapeHtml(message.time_label) + '</span>' +
                    '</div>' +
                '</article>';
        }).join("");
    };

    const markSeen = function () {
        if (!activeTargetId) {
            return Promise.resolve();
        }

        return window.ChatHttp.request(seenEndpoint, {
            method: "POST",
            body: new URLSearchParams({ type: activeChatType, id: String(activeTargetId) }).toString()
        }).catch(function () {
            return null;
        });
    };

    const loadUsers = async function () {
        const response = await window.ChatHttp.request(
            usersEndpoint + "?active_chat_type=" + encodeURIComponent(activeChatType) + "&active_user_id=" + encodeURIComponent(String(activeTargetId || ""))
        );
        renderRoster(response.users || []);
    };

    const loadMessages = async function (scrollToBottomAfterLoad) {
        if (!activeTargetId || !boardElement) {
            return;
        }

        const response = await window.ChatHttp.request(
            messagesEndpoint + "?type=" + encodeURIComponent(activeChatType) + "&id=" + encodeURIComponent(String(activeTargetId))
        );
        renderHeader(response.selected_user);
        renderMessages(response.messages || []);
        await markSeen();

        if (scrollToBottomAfterLoad) {
            scrollMessages();
        }
    };

    if (rosterElement) {
        rosterElement.addEventListener("click", function (event) {
            const target = event.target.closest("[data-chat-target]");

            if (!target) {
                return;
            }

            const userId = Number(target.dataset.chatTarget || 0);
            const chatType = target.dataset.chatType || "direct";

            if (!userId || (userId === activeTargetId && chatType === activeChatType)) {
                return;
            }

            window.location.href = chatPage + "?type=" + encodeURIComponent(chatType) + "&id=" + userId;
        });
    }

    if (formElement) {
        formElement.addEventListener("submit", async function (event) {
            event.preventDefault();

            const hasText = messageInput && messageInput.value.trim() !== "";
            const hasImage = mediaInput && mediaInput.files && mediaInput.files.length > 0;

            if (!hasText && !hasImage) {
                setComposerHint("Write a message or choose one image.");
                return;
            }

            const formData = new FormData(formElement);
            formData.set("chat_type", activeChatType);
            formData.set("target_id", String(activeTargetId));
            const submitButton = formElement.querySelector('button[type="submit"]');

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = "Sending...";
            }

            try {
                await window.ChatHttp.request(sendEndpoint, {
                    method: "POST",
                    body: formData
                });

                resetComposer();
                await loadUsers();
                await loadMessages(true);
            } catch (error) {
                setComposerHint(error.message || "Message could not be sent.");
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = "Send";
                }
            }
        });
    }

    if (messageInput) {
        messageInput.addEventListener("keydown", function (event) {
            if (event.key === "Enter" && !event.shiftKey) {
                event.preventDefault();
                if (formElement) {
                    formElement.requestSubmit();
                }
            }
        });
    }

    if (mediaInput) {
        mediaInput.addEventListener("change", function () {
            if (mediaInput.files && mediaInput.files.length && messageInput) {
                if (attachLabel) {
                    attachLabel.textContent = mediaInput.files[0].name;
                }
                setComposerHint("");
                messageInput.focus();
            } else if (attachLabel) {
                attachLabel.textContent = "Attach image";
            }
        });
    }

    if (activeTargetId) {
        loadUsers().catch(function () {
            return null;
        });
        loadMessages(true).catch(function () {
            return null;
        });

        window.setInterval(function () {
            if (document.hidden) {
                return;
            }

            loadUsers().catch(function () {
                return null;
            });
            loadMessages(false).catch(function () {
                return null;
            });
        }, 4000);
    }

    if (!activeTargetId) {
        loadUsers().catch(function () {
            return null;
        });
    }
})();
