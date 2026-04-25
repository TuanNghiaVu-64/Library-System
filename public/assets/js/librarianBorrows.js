// Handle card toggle form submission
window.handleCardToggle = async (form) => {
    const borrowerRegion = document.getElementById('borrowerResultsRegion');
    if (!borrowerRegion) return;

    const filterForm = document.getElementById('borrowerFilterForm');
    const formData = new FormData(form);

    borrowerRegion.classList.add('is-loading');

    try {
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            // Refresh the borrower list with current filters
            const filterFormData = new FormData(filterForm);
            const params = new URLSearchParams();

            for (const [key, value] of filterFormData.entries()) {
                params.set(key, value);
            }

            const targetUrl = `${window.location.pathname}?${params.toString()}`;

            const listResponse = await fetch(targetUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const html = await listResponse.text();
            borrowerRegion.innerHTML = html;
            window.history.replaceState({}, '', targetUrl);
        } else {
            console.error('Failed to toggle card status');
        }
    } catch (error) {
        console.error('Error toggling card status:', error);
    } finally {
        borrowerRegion.classList.remove('is-loading');
    }
};

(() => {
    const filterForm = document.getElementById('borrowerFilterForm');
    const borrowerRegion = document.getElementById('borrowerResultsRegion');
    const searchInput = filterForm?.querySelector('input[name="search"]');
    const filterSelects = filterForm?.querySelectorAll('select');
    let debounceTimer;
    let activeController;

    if (!filterForm || !borrowerRegion) {
        return;
    }

    const refreshBorrowers = async () => {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            params.set(key, value);
        }

        const targetUrl = `${window.location.pathname}?${params.toString()}`;

        if (activeController) {
            activeController.abort();
        }
        activeController = new AbortController();
        borrowerRegion.classList.add('is-loading');

        try {
            const response = await fetch(targetUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: activeController.signal
            });
            const html = await response.text();
            borrowerRegion.innerHTML = html;
            window.history.replaceState({}, '', targetUrl);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Unable to refresh borrower list:', error);
            }
        } finally {
            borrowerRegion.classList.remove('is-loading');
        }
    };

    const debouncedRefresh = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(refreshBorrowers, 300);
    };

    if (searchInput) {
        searchInput.addEventListener('input', debouncedRefresh);
    }

    filterSelects?.forEach((select) => {
        select.addEventListener('change', refreshBorrowers);
    });

    // Initial load of the table
    refreshBorrowers();
})();
