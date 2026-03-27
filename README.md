# 💬 Real-Time Chat Application (Full-Stack)

A scalable and feature-rich **real-time chat application** built using **PHP, MySQL, and JavaScript (AJAX)**.
This project includes a complete **user messaging system** along with a powerful **admin dashboard** for managing users, conversations, and system activity.

Designed with modular architecture and clean separation of concerns, this application serves as a strong foundation for building modern communication platforms.

---

## 🚀 Live Features

### 👤 User Features

* 🔐 Secure User Authentication (Login / Register)
* 💬 Real-Time Messaging using AJAX (no page reloads)
* 👥 Dynamic User List with Active Conversations
* 🟢 Online / Offline Status Indicators
* ✅ Message Seen / Delivered Status
* 🔎 Search Users Functionality
* 📱 Fully Responsive Interface

---

### 🧑‍💼 Admin Features

* 📊 Admin Dashboard Overview
* 👥 Manage Users (View / Delete / Control)
* 💬 Monitor Conversations
* 🗑️ Delete Messages or Users
* ⚙️ System Control Panel

---

## 🏗️ Project Architecture

The application follows a structured and modular approach:

```
chat-app/
│
├── api/               # Backend API endpoints (AJAX handling)
├── assets/            # CSS, JS, Images
├── auth/              # Authentication (login, register)
├── chat/              # Chat UI & logic
├── config/            # Database configuration
├── admin/             # Admin dashboard & controls
├── includes/          # Reusable components
└── database/          # SQL file for setup
```

---

## 🛠️ Tech Stack

| Layer    | Technology Used                          |
| -------- | ---------------------------------------- |
| Frontend | HTML, CSS, JavaScript (AJAX)             |
| Backend  | PHP (Core PHP)                           |
| Database | MySQL                                    |
| Server   | Apache (XAMPP / InfinityFree compatible) |

---

## ⚙️ Installation & Setup

### 1️⃣ Clone the Repository

```bash
git clone https://github.com/sakshampandey380/Chat_App.git
cd chat-app
```

### 2️⃣ Setup Database

* Open **phpMyAdmin**
* Create a new database (e.g., `chat_app`)
* Import the SQL file:

```
database/chat-app.sql
```

### 3️⃣ Configure Database

Edit:

```
config/db.php
```

Update credentials:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "chat_app";
```

### 4️⃣ Run the Project

* Start Apache & MySQL (XAMPP)
* Open browser:

```
http://localhost/chat-app
```

---

## 🔄 How It Works

* AJAX handles all chat communication asynchronously
* APIs in `/api/` manage:

  * Sending messages
  * Fetching chats
  * Updating message status
* PHP processes backend logic and interacts with MySQL
* Admin panel provides centralized control over system data

---

## 🔐 Security Considerations

* Input validation implemented in backend
* Database queries structured for safe execution
* Authentication system restricts unauthorized access

> ⚠️ Note: Further improvements like prepared statements, token-based auth, and rate limiting can be added for production-level security.

---

## 📈 Future Enhancements

* 🔔 Real-time notifications (WebSockets)
* 📎 File/Image sharing
* 🎥 Voice/Video calling integration
* 🌐 REST API conversion
* 🔒 JWT Authentication
* ☁️ Deployment on cloud (AWS / Render)

---

## 📸 Screenshots 


```
/screenshots/login.png
/screenshots/chat.png
/screenshots/admin.png
```

---

## 🤝 Contribution

Contributions are welcome!
Feel free to fork this repository and submit pull requests.

---

## 📄 License

This project is open-source and available under the **MIT License**.

---

## 🙌 Acknowledgements

* Built as a full-stack learning project
* Focused on real-world chat system implementation
* Designed for scalability and extensibility

---

## ⭐ Support

If you like this project, consider giving it a ⭐ on GitHub! 

---
Made With ALL By Saksham Pandey
