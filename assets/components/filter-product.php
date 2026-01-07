<div class="bk-dropdown">
    <div class="bk-filter-block">
        <label class="bk-label bk-filter-label">Products</label>

        <button type="button" class="bk-select bk-filter-input bk-select-dropdown"
            onclick="toggleProductDropdown(this)">

            <span class="bk-select-text">
                <?= isset($_GET['products']) ? "Filtered products" : "All products" ?>
            </span>

            <img src="/PROGNET/images/icons/park_down.svg" class="bk-select-icon" alt="dropdown">
        </button>
    </div>

    <div class="bk-checkbox-panel">
        <div class="bk-search-wrapper">
            <input type="text" class="bk-search-input" placeholder="Search products" onkeyup="filterProducts(this)">
            <img src="/PROGNET/images/icons/search.svg" class="bk-search-icon">
        </div>

        <div class="bk-product-list">
            <?php foreach ($products as $p): ?>
                <label class="bk-product-item">
                    <input type="checkbox" name="products[]" value="<?= $p['id'] ?>" <?= (isset($_GET['products']) && in_array($p['id'], $_GET['products'])) ? "checked" : "" ?>>
                    <?= htmlspecialchars($p['title']) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
</div>