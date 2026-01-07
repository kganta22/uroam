function toggleProductDropdown(button) {
    const wrapper = button.closest(".bk-dropdown");
    const dropdown = wrapper.querySelector(".bk-checkbox-panel");

    if (!dropdown) return; // safety

    const isOpen = dropdown.style.display === "block";

    dropdown.style.display = isOpen ? "none" : "block";
    button.classList.toggle("is-open", !isOpen);
}

function filterProducts(input) {
    const wrapper = input.closest(".bk-dropdown");
    const items = wrapper.querySelectorAll(".bk-product-item");

    const value = input.value.toLowerCase();

    items.forEach(item => {
        const text = item.innerText.toLowerCase();
        item.style.display = text.includes(value) ? "flex" : "none";
    });
}

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".bk-dropdown input[type='checkbox']").forEach(cb => {
        cb.addEventListener("change", () => {
            document.getElementById("filterForm").submit();
        });
    });
});
