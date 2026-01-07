const body = document.body;
const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("sidebarToggle");
const wrapper = document.querySelector(".content-wrapper") || document.getElementById("content-wrapper");
const sidebarLogo = document.getElementById("sidebarLogo");

if (wrapper) {
  wrapper.classList.add("content-expanded");
  body.classList.add("sidebar-expanded");
}

toggleBtn.addEventListener("click", () => {
  const collapsed = sidebar.classList.toggle("collapsed");

  wrapper.classList.remove("content-expanded", "content-collapsed");
  wrapper.classList.add(collapsed ? "content-collapsed" : "content-expanded");

  body.classList.remove("sidebar-expanded", "sidebar-collapsed");
  body.classList.add(collapsed ? "sidebar-collapsed" : "sidebar-expanded");

  sidebarLogo.style.opacity = collapsed ? "0" : "1";
  toggleBtn.classList.toggle("rotated", collapsed);

  // FORCE close all dropdowns when collapsed
  if (collapsed) {
    document
      .querySelectorAll(".sidebar-dropdown.open")
      .forEach(menu => menu.classList.remove("open"));
  }
});

document.querySelectorAll(".sidebar-dropdown").forEach(menu => {
  const header = menu.querySelector(".sidebar-dropdown-header");

  header.addEventListener("click", () => {

    // Jangan buka dropdown saat sidebar collapsed
    if (sidebar.classList.contains("collapsed")) return;

    menu.classList.toggle("open");
  });
});

document.addEventListener("click", (e) => {
  document.querySelectorAll(".sidebar-dropdown").forEach(menu => {
    const header = menu.querySelector(".sidebar-dropdown-header");

    if (!menu.contains(e.target) && !header.contains(e.target)) {
      menu.classList.remove("open");
    }
  });
});
