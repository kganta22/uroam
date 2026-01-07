const profileToggle = document.getElementById("profileToggle");
const profileMenu = document.getElementById("profileMenu");

// Toggle profile dropdown
profileToggle.onclick = (e) => {
  e.stopPropagation();
  profileMenu.style.display = profileMenu.style.display === "block" ? "none" : "block";
};

// Close if clicked outside
document.addEventListener("click", () => {
  profileMenu.style.display = "none";
});
