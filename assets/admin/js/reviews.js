document.querySelectorAll('.rating-filters input[type="checkbox"]').forEach(cb => {
  cb.addEventListener('change', () => {
    document.getElementById('filterForm').submit();
  });
});

document.querySelectorAll(".rv-detail-btn").forEach(btn => {
  btn.addEventListener("click", function () {

    const detailBox = this.parentElement.nextElementSibling;

    if (detailBox.style.display === "block") {
      detailBox.style.display = "none";
      this.textContent = "Show details";
    } else {
      detailBox.style.display = "block";
      this.textContent = "Hide details";
    }
  });
});


document.querySelectorAll("input[name='rating[]']").forEach(cb => {
  cb.addEventListener("change", () => {
    document.getElementById("filterForm").submit();
  });
});

