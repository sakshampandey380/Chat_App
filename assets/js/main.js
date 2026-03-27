(function () {
    const root = document.documentElement;
    const basePath = document.body.dataset.basePath || "";
    const statusUrl = basePath + "/api/update_status.php";

    document.querySelectorAll("[data-flash-close]").forEach(function (button) {
        button.addEventListener("click", function () {
            const flash = button.closest(".flash");
            if (flash) {
                flash.remove();
            }
        });
    });

    document.addEventListener("mousemove", function (event) {
        const x = (event.clientX / window.innerWidth) * 100;
        const y = (event.clientY / window.innerHeight) * 100;
        root.style.setProperty("--pointer-x", x + "%");
        root.style.setProperty("--pointer-y", y + "%");
    });

    document.querySelectorAll("textarea").forEach(function (textarea) {
        const resize = function () {
            textarea.style.height = "auto";
            textarea.style.height = Math.min(textarea.scrollHeight, 180) + "px";
        };

        textarea.addEventListener("input", resize);
        resize();
    });

    if (!document.querySelector(".dashboard-layout")) {
        return;
    }

    const postStatus = function (status, useBeacon) {
        const body = new URLSearchParams({ status: status });

        if (useBeacon && navigator.sendBeacon) {
            navigator.sendBeacon(statusUrl, body);
            return;
        }

        fetch(statusUrl, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
            },
            body: body.toString()
        }).catch(function () {
            return null;
        });
    };

    postStatus("online");

    const interval = window.setInterval(function () {
        postStatus(document.hidden ? "away" : "online");
    }, 30000);

    document.addEventListener("visibilitychange", function () {
        postStatus(document.hidden ? "away" : "online");
    });

    window.addEventListener("beforeunload", function () {
        postStatus("offline", true);
        window.clearInterval(interval);
    });
})();
