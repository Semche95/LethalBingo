/**
 * Sends a JSON POST request carrying the CSRF token in the `X-CSRF-Token`
 * header, and throws when the server does not answer with a success status.
 */
export async function postJson(url: string, csrfToken: string, body?: object): Promise<Response> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-Token': csrfToken,
        },
        body: body ? JSON.stringify(body) : null,
    })

    if (!response.ok) {
        throw new Error(`POST ${url} failed with status ${response.status}`)
    }

    return response
}
