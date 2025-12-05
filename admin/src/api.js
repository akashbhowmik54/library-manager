export const API_BASE = libraryApp.rest_url;
export const NONCE = libraryApp.nonce;

export async function apiGet(path) {
  const res = await fetch(`${API_BASE}${path}`);
  return res.json();
}

export async function apiPost(path, data) {
  const res = await fetch(`${API_BASE}${path}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-WP-Nonce": NONCE
    },
    body: JSON.stringify(data)
  });
  return res.json();
}

export async function apiPut(path, data) {
  const res = await fetch(`${API_BASE}${path}`, {
    method: "PUT",
    headers: {
      "Content-Type": "application/json",
      "X-WP-Nonce": NONCE
    },
    body: JSON.stringify(data)
  });
  return res.json();
}

export async function apiDelete(path) {
  return fetch(`${API_BASE}${path}`, {
    method: "DELETE",
    headers: { "X-WP-Nonce": NONCE }
  });
}
