(function () {
    const modal = document.querySelector("[data-group-modal]");
    const openButton = document.querySelector("[data-open-group-modal]");

    if (!modal || !openButton) {
        return;
    }

    const openModal = function () {
        modal.hidden = false;
        document.body.style.overflow = "hidden";
    };

    const closeModal = function () {
        modal.hidden = true;
        document.body.style.overflow = "";
    };

    openButton.addEventListener("click", openModal);

    modal.addEventListener("click", function (event) {
        if (event.target.matches("[data-close-group-modal]")) {
            closeModal();
        }
    });

    document.querySelectorAll("[data-quick-group]").forEach(function (button) {
        button.addEventListener("click", function () {
            const memberId = button.dataset.memberId;
            openModal();

            if (!memberId) {
                return;
            }

            const checkbox = modal.querySelector('input[name="member_ids[]"][value="' + memberId + '"]');
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && !modal.hidden) {
            closeModal();
        }
    });
})();
