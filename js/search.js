let searchTimeout = null;

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');

    if (!searchInput || !searchResults) return;

    let searchTimeout = null;

    function performSearch(term) {
        if (term.length === 0) {
            searchResults.style.display = 'none';
            return;
        }

        fetch('search.php?q=' + encodeURIComponent(term))
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    searchResults.innerHTML = '<div class="p-3 text-danger">Error: ' + data.message + '</div>';
                    searchResults.style.display = 'block';
                    return;
                }

                if (data.length > 0) {
                    let html = '<div class="list-group">';
                    data.forEach(product => {
                        html += `
                            <div class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <a href="product.php?id=${product.id}" class="d-flex align-items-center text-decoration-none text-dark flex-grow-1">
                                        <img src="${product.image}" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                        <div>
                                            <div class="fw-bold">${product.name}</div>
                                            <div class="text-muted">${product.price} Ft</div>
                                        </div>
                                    </a>
                                    <button onclick="event.preventDefault(); event.stopPropagation(); addToCart(${product.id})" class="btn btn-link">
                                        <i class="bi bi-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    searchResults.innerHTML = html;
                } else {
                    searchResults.innerHTML = '<div class="p-3">No products found</div>';
                }
                searchResults.style.display = 'block';
            })
            .catch(error => {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div class="p-3 text-danger">Error occurred while searching</div>';
                searchResults.style.display = 'block';
            });
    }

    // Input event handler
    searchInput.addEventListener('input', function() {
        const term = this.value.trim();
        
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        searchTimeout = setTimeout(() => {
            performSearch(term);
        }, 300);
    });

    // Click event handler for the search input
    searchInput.addEventListener('click', function() {
        const term = this.value.trim();
        if (term.length > 0) {
            performSearch(term);
        }
    });

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    // Close search results when pressing Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchResults.style.display = 'none';
            searchInput.blur();
        }
    });
});

function addToCart(productId) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', 1);

    fetch('update_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cart-count').textContent = data.cartCount;
        }
    })
    .catch(error => console.error('Error:', error));
}
