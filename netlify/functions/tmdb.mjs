const TMDB_BASE = 'https://api.themoviedb.org/3';
const PATH_ALLOWLIST = /^\/(?:search|discover|genre|movie|tv|trending)\/[A-Za-z0-9_-]+(?:\/[A-Za-z0-9_-]+)*$/;

export default async (request) => {
  if (request.method !== 'GET') {
    return Response.json({ error: 'Method not allowed' }, { status: 405 });
  }
  const apiKey = process.env.TMDB_API_KEY;
  if (!apiKey) {
    return Response.json({ error: 'TMDB_API_KEY is not configured on the server.' }, { status: 503 });
  }
  const incoming = new URL(request.url);
  const path = incoming.searchParams.get('path') || '';
  if (!PATH_ALLOWLIST.test(path) || path.includes('..')) {
    return Response.json({ error: 'Unsupported TMDB endpoint.' }, { status: 400 });
  }
  const upstream = new URL(TMDB_BASE + path);
  for (const [key, value] of incoming.searchParams.entries()) {
    if (key !== 'path' && key !== 'api_key') upstream.searchParams.set(key, value);
  }
  upstream.searchParams.set('api_key', apiKey);
  try {
    const response = await fetch(upstream, {
      headers: { Accept: 'application/json' },
      signal: AbortSignal.timeout(15000)
    });
    const body = await response.text();
    return new Response(body, {
      status: response.status,
      headers: {
        'Content-Type': response.headers.get('content-type') || 'application/json; charset=utf-8',
        'Cache-Control': 'public, max-age=60, s-maxage=300',
        'X-Content-Type-Options': 'nosniff'
      }
    });
  } catch {
    return Response.json({ error: 'TMDB request failed.' }, { status: 502 });
  }
};
