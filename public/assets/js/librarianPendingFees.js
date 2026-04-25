// Handle card toggle form submission
window.handleCardToggle = async (form) => {
    const dataRegion = document.getElementById('pendingFeesResultsRegion');
    if (!dataRegion) return;

    const filterForm = document.getElementById('pendingFeesFilterForm');
    const formData = new FormData(form);

    dataRegion.classList.add('is-loading');

    try {
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            // Refresh the list with current filters
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
            dataRegion.innerHTML = html;
            window.history.replaceState({}, '', targetUrl);
        } else {
            console.error('Failed to toggle card status');
        }
    } catch (error) {
        console.error('Error toggling card status:', error);
    } finally {
        dataRegion.classList.remove('is-loading');
    }
};

(() => {
    const filterForm = document.getElementById('pendingFeesFilterForm');
    const dataRegion = document.getElementById('pendingFeesResultsRegion');
    const searchInput = filterForm?.querySelector('input[name="search"]');
    const filterSelects = filterForm?.querySelectorAll('select');
    let debounceTimer;
    let activeController;

    if (!filterForm || !dataRegion) {
        return;
    }

    const refreshData = async () => {
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
        dataRegion.classList.add('is-loading');

        try {
            const response = await fetch(targetUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: activeController.signal
            });
            const html = await response.text();
            dataRegion.innerHTML = html;
            window.history.replaceState({}, '', targetUrl);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Unable to refresh data list:', error);
            }
        } finally {
            dataRegion.classList.remove('is-loading');
        }
    };

    const debouncedRefresh = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(refreshData, 300);
    };

    if (searchInput) {
        searchInput.addEventListener('input', debouncedRefresh);
    }

    filterSelects?.forEach((select) => {
        select.addEventListener('change', refreshData);
    });

    // Initial load of the table
    refreshData();
})();
