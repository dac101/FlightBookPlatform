function getCsrfToken() {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
}

function buildQuery(params = {}) {
    const searchParams = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            value.forEach((entry) => {
                if (entry !== null && entry !== undefined && entry !== '') {
                    searchParams.append(`${key}[]`, entry);
                }
            });
            return;
        }

        if (value !== null && value !== undefined && value !== '') {
            searchParams.set(key, value);
        }
    });

    const query = searchParams.toString();

    return query ? `?${query}` : '';
}

async function request(url, options = {}) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...options.headers,
    };

    if (options.body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    const csrfToken = getCsrfToken();

    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers,
        body:
            options.body !== undefined
                ? JSON.stringify(options.body)
                : undefined,
    });

    const contentType = response.headers.get('content-type') || '';
    const payload = contentType.includes('application/json')
        ? await response.json()
        : null;

    if (!response.ok) {
        const error = new Error(payload?.message || 'Request failed.');
        error.status = response.status;
        error.errors = payload?.errors || {};
        throw error;
    }

    return payload;
}

export const api = {
    buildQuery,
    get(url, params = {}) {
        return request(`${url}${buildQuery(params)}`);
    },
    post(url, body) {
        return request(url, { method: 'POST', body });
    },
    patch(url, body) {
        return request(url, { method: 'PATCH', body });
    },
    put(url, body) {
        return request(url, { method: 'PUT', body });
    },
    delete(url) {
        return request(url, { method: 'DELETE' });
    },
};
