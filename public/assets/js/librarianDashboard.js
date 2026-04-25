(() => {
    const filterForm = document.getElementById('librarianFilterForm');
    const booksRegion = document.getElementById('librarianBooksRegion');
    const searchInput = filterForm?.querySelector('input[name="search"]');
    const filterSelects = filterForm?.querySelectorAll('select');
    const yearMinInput = document.getElementById('publishYearMin');
    const yearMaxInput = document.getElementById('publishYearMax');
    const yearMinValue = document.getElementById('publishYearMinValue');
    const yearMaxValue = document.getElementById('publishYearMaxValue');
    let debounceTimer;
    let activeController;

    if (!filterForm || !booksRegion) {
        return;
    }

    const syncYearLabels = () => {
        if (!yearMinInput || !yearMaxInput || !yearMinValue || !yearMaxValue) {
            return;
        }

        let minYear = Number(yearMinInput.value);
        let maxYear = Number(yearMaxInput.value);

        if (minYear > maxYear) {
            if (document.activeElement === yearMinInput) {
                maxYear = minYear;
                yearMaxInput.value = String(maxYear);
            } else {
                minYear = maxYear;
                yearMinInput.value = String(minYear);
            }
        }

        yearMinValue.textContent = String(minYear);
        yearMaxValue.textContent = String(maxYear);
    };

    const refreshBooks = async () => {
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
        booksRegion.classList.add('is-loading');

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
            const newBooksRegion = doc.getElementById('librarianBooksRegion');

            if (newBooksRegion) {
                booksRegion.innerHTML = newBooksRegion.innerHTML;
                window.history.replaceState({}, '', targetUrl);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Unable to refresh book list:', error);
            }
        } finally {
            booksRegion.classList.remove('is-loading');
        }
    };

    const debouncedRefresh = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(refreshBooks, 300);
    };

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        refreshBooks();
    });

    if (searchInput) {
        searchInput.addEventListener('input', debouncedRefresh);
    }

    filterSelects?.forEach((select) => {
        select.addEventListener('change', refreshBooks);
    });

    [yearMinInput, yearMaxInput].forEach((input) => {
        if (!input) {
            return;
        }

        input.addEventListener('input', () => {
            syncYearLabels();
            debouncedRefresh();
        });
    });

    syncYearLabels();
})();
