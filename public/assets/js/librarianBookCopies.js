window.handleToggleCopyStatus = async (form) => {
    const tableRegion = document.getElementById('bookCopiesRegion');
    const filterForm = document.getElementById('copyFilterForm');
    if (!tableRegion || !filterForm) return;

    const formData = new FormData(form);
    tableRegion.classList.add('is-loading');

    try {
        const response = await fetch(window.location.pathname + window.location.search, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (response.ok) {
            const filterFormData = new FormData(filterForm);
            const params = new URLSearchParams(window.location.search);
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
            const newTableRegion = doc.getElementById('bookCopiesRegion');

            if (newTableRegion) {
                tableRegion.innerHTML = newTableRegion.innerHTML;
                window.history.replaceState({}, '', targetUrl);
            }
            
            // Check for potential errors that the PHP might render:
            const newErrors = doc.querySelectorAll('.alert-error');
            if (newErrors.length > 0) {
                // To display it smoothly, recreate and append to body or top.
                // Or simply let the error flash in a simplified way:
                const errorMsg = newErrors[0].innerText;
                alert(errorMsg); 
            }
        }
    } catch (error) {
        console.error('Error toggling status:', error);
    } finally {
        tableRegion.classList.remove('is-loading');
    }
};

(() => {
    const filterForm = document.getElementById('copyFilterForm');
    const copiesRegion = document.getElementById('bookCopiesRegion');
    const filterSelects = filterForm?.querySelectorAll('select');
    let activeController;

    if (!filterForm || !copiesRegion) {
        return;
    }

    const refreshCopies = async () => {
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
        copiesRegion.classList.add('is-loading');

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
            const newCopiesRegion = doc.getElementById('bookCopiesRegion');

            if (newCopiesRegion) {
                copiesRegion.innerHTML = newCopiesRegion.innerHTML;
                window.history.replaceState({}, '', targetUrl);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Unable to refresh copy list:', error);
            }
        } finally {
            copiesRegion.classList.remove('is-loading');
        }
    };

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        refreshCopies();
    });

    filterSelects?.forEach((select) => {
        select.addEventListener('change', refreshCopies);
    });
})();
