async function apiCall(action, method = 'GET', body = null) {
    let url = `backend/api/router.php?action=${action}`;
    let options = { method };
    if (body) {
        options.body = body;
    }
    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            const text = await response.text();
            throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
        }
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}