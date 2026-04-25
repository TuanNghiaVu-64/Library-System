window.handleToggleUserActive = async (form) => {
    const tableRegion = document.getElementById('userTableRegion');
    const filterForm = document.getElementById('userFilterForm');
    if (!tableRegion || !filterForm) return;

    const formData = new FormData(form);
    tableRegion.classList.add('is-loading');

    try {
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (response.ok) {
            const filterFormData = new FormData(filterForm);
            const params = new URLSearchParams();
            for (const [key, value] of filterFormData.entries()) {
                params.set(key, value);
            }
            const targetUrl = `${window.location.pathname}?${params.toString()}`;

            const listResponse = await fetch(targetUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await listResponse.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTableRegion = doc.getElementById('userTableRegion');

            if (newTableRegion) {
                tableRegion.innerHTML = newTableRegion.innerHTML;
                window.history.replaceState({}, '', targetUrl);
            }
        }
    } catch (error) {
        console.error('Error toggling status:', error);
    } finally {
        tableRegion.classList.remove('is-loading');
    }
};

(() => {
    const filterForm = document.getElementById('userFilterForm');
    const tableRegion = document.getElementById('userTableRegion');
    const searchInput = filterForm?.querySelector('input[name="search"]');
    const roleSelect = filterForm?.querySelector('select[name="role"]');
    const statusSelect = filterForm?.querySelector('select[name="is_active"]');
    let debounceTimer;
    let activeController;

    if (!filterForm || !tableRegion) {
        return;
    }

    const refreshTable = async () => {
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
        tableRegion.classList.add('is-loading');

        try {
            const response = await fetch(targetUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: activeController.signal
            });
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTableRegion = doc.getElementById('userTableRegion');

            if (newTableRegion) {
                tableRegion.innerHTML = newTableRegion.innerHTML;
                window.history.replaceState({}, '', targetUrl);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Unable to refresh user table:', error);
            }
        } finally {
            tableRegion.classList.remove('is-loading');
        }
    };

    const debouncedRefresh = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(refreshTable, 300);
    };

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        refreshTable();
    });

    if (searchInput) {
        searchInput.addEventListener('input', debouncedRefresh);
    }
    if (roleSelect) {
        roleSelect.addEventListener('change', refreshTable);
    }
    if (statusSelect) {
        statusSelect.addEventListener('change', refreshTable);
    }
})();
