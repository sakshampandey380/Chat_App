(function () {
    async function request(url, options) {
        const config = options || {};
        const headers = config.headers ? { ...config.headers } : {};

        if (!(config.body instanceof FormData) && !headers["Content-Type"] && config.body) {
            headers["Content-Type"] = "application/x-www-form-urlencoded; charset=UTF-8";
        }

        const response = await fetch(url, {
            credentials: "same-origin",
            ...config,
            headers
        });

        const contentType = response.headers.get("content-type") || "";
        const payload = contentType.includes("application/json")
            ? await response.json()
            : await response.text();

        if (!response.ok) {
            const message = typeof payload === "object" && payload.message
                ? payload.message
                : "Request failed.";
            throw new Error(message);
        }

        return payload;
    }

    window.ChatHttp = { request };
})();
