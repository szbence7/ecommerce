        </div> <!-- Close container div from header -->
        
        <footer class="bg-light mt-5 py-3">
            <div class="container">
                <div class="text-center">
                    &copy; <?= date('Y') ?> Your Store Name. All rights reserved.
                </div>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://unpkg.com/lucide@latest"></script>
        <script>
            lucide.createIcons();
        </script>
        <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        
        searchInput.addEventListener('input', function() {
            if (this.value.length >= 3) {
                fetch('search.php?q=' + this.value)
                    .then(response => response.json())
                    .then(data => {
                        let html = '';
                        data.forEach(product => {
                            html += `
                                <a href="${product.url}" class="d-block p-2 text-decoration-none text-dark hover-bg-light">
                                    ${product.name} - $${product.price}
                                </a>
                            `;
                        });
                        searchResults.innerHTML = html;
                        searchResults.style.display = data.length ? 'block' : 'none';
                    });
            } else {
                searchResults.style.display = 'none';
            }
        });

        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
        </script>
    </body>
</html> 