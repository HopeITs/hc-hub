document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('[data-password-toggle]').forEach(button => {
        button.addEventListener("click", function () {
            const inputId = this.getAttribute("data-password-toggle");
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + "_icon");

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            }
        });
    });
});
