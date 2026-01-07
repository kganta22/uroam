/**
 * Tours Page - Main Functionality
 * Handles product filtering, search, and category navigation
 */

const API_BASE = '/PROGNET/customer/api/tours-data.php';
const searchInput = document.getElementById('searchInput');
const categoriesTabs = document.getElementById('categoriesTabs');
const productsGrid = document.getElementById('productsGrid');
const tabsWrapper = document.querySelector('.t-tabs-wrapper');
const tabs = document.querySelector('.t-tabs');
const prevBtn = document.querySelector('.t-tabs-prev');
const nextBtn = document.querySelector('.t-tabs-next');
const scrollAmount = 400;

/**
 * Initialize page functionality
 */
document.addEventListener('DOMContentLoaded', () => {
	updateButtonVisibility();

	// Add click handlers to category tabs
	document.querySelectorAll('.t-tab[data-category]').forEach(tab => {
		tab.addEventListener('click', async (e) => {
			const categoryId = e.target.dataset.category;

			document.querySelectorAll('.t-tab').forEach(btn => btn.classList.remove('active'));
			e.target.classList.add('active');

			if (categoryId === 'top-products') {
				await loadTopProducts();
			} else {
				await filterByCategory(categoryId);
			}
		});
	});
});

/**
 * Fetch data from API
 */
async function fetchAPI(action, params = {}) {
	const url = new URL(API_BASE, window.location.origin);
	url.searchParams.append('action', action);
	Object.keys(params).forEach(key => {
		url.searchParams.append(key, params[key]);
	});

	try {
		const response = await fetch(url);
		return await response.json();
	} catch (error) {
		console.error('API Error:', error);
		return { success: false, message: error.message };
	}
}

/**
 * Load top products from API
 */
async function loadTopProducts() {
	const result = await fetchAPI('get_top_products');

	if (!result.success) {
		console.error('Failed to load top products:', result.message);
		return;
	}

	displayProducts(result.data);
}

/**
 * Filter products by category
 */
async function filterByCategory(categoryId) {
	const result = await fetchAPI('filter_by_category', { category_id: categoryId });

	if (!result.success) {
		console.error('Failed to filter products:', result.message);
		return;
	}

	displayProducts(result.data);
}

/**
 * Handle search input with debouncing
 */
let searchTimeout;
searchInput.addEventListener('input', (e) => {
	clearTimeout(searchTimeout);

	if (e.target.value.trim() === '') {
		loadTopProducts();
		return;
	}

	searchTimeout = setTimeout(async () => {
		const result = await fetchAPI('search', { q: e.target.value });

		if (!result.success) {
			console.error('Search failed:', result.message);
			return;
		}

		displayProducts(result.data);
	}, 300);
});

/**
 * Display products in the grid
 */
function displayProducts(products) {
	productsGrid.innerHTML = '';

	if (products.length === 0) {
		productsGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #6b7280; padding: 40px 20px;">No products found</p>';
		return;
	}

	products.forEach(product => {
		const card = createProductCard(product);
		productsGrid.appendChild(card);
	});
}

/**
 * Create a product card element
 */
function createProductCard(product) {
	const article = document.createElement('article');
	article.className = 't-card';

	const imageUrl = product.thumbnail || '/PROGNET/images/placeholder.png';
	const duration = product.duration_hours ? formatDuration(product.duration_hours) : 'N/A';
	const price = formatPrice(product.price);

	article.innerHTML = `
		<a href="/PROGNET/customer/tour-detail.php?id=${product.id}" class="t-card-link" aria-label="${product.title}">
			<div class="t-media" style="background-image:url('${imageUrl}');"></div>
			<div class="t-overlay">
				<h3 class="t-title">${product.title}</h3>
				<div class="t-meta">
					<div class="t-meta-row">
						<span class="t-meta-item">
							<img src="/PROGNET/images/icons/clock.svg" class="t-ico" alt="">
							<span class="t-text">${duration}</span>
						</span>
					</div>
				</div>
				<div class="t-price">
					<span class="t-price-label">Start from</span>
					<span class="t-price-value">${price}</span>
				</div>
			</div>
		</a>
	`;

	return article;
}

/**
 * Format duration hours to readable string
 */
function formatDuration(hours) {
	if (hours >= 24) {
		const days = Math.ceil(hours / 24);
		return `${days} day${days > 1 ? 's' : ''}`;
	}
	return `${hours} hours`;
}

/**
 * Format price to IDR currency format
 */
function formatPrice(price) {
	if (!price) return 'Contact for price';
	return 'Rp ' + price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

/**
 * Update visibility of navigation buttons based on scroll position
 */
function updateButtonVisibility() {
	const scrollLeft = tabs.scrollLeft;
	const maxScroll = tabs.scrollWidth - tabs.clientWidth;

	prevBtn.style.display = scrollLeft > 0 ? 'flex' : 'none';
	nextBtn.style.display = scrollLeft < maxScroll ? 'flex' : 'none';
}

/**
 * Handle next button click
 */
if (nextBtn && tabs) {
	nextBtn.addEventListener('click', () => {
		tabs.scrollLeft += scrollAmount;
		setTimeout(updateButtonVisibility, 50);
	});
}

/**
 * Handle previous button click
 */
if (prevBtn && tabs) {
	prevBtn.addEventListener('click', () => {
		tabs.scrollLeft -= scrollAmount;
		setTimeout(updateButtonVisibility, 50);
	});
}

window.addEventListener('load', updateButtonVisibility);
setTimeout(updateButtonVisibility, 500);
