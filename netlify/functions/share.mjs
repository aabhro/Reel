const TMDB_BASE = 'https://api.themoviedb.org/3';
const IMAGE_BASE = 'https://image.tmdb.org/t/p/';
const SHARE_PATHS = ['/movie/:id', '/movie/:id/', '/tv/:id', '/tv/:id/'];

export const config = {
  path: SHARE_PATHS,
  method: ['GET', 'HEAD']
};

function escapeHtml(value = '') {
  return String(value).replace(/[&<>"']/g, character => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  })[character]);
}

function text(value, limit = 260) {
  return String(value || '').replace(/\s+/g, ' ').trim().slice(0, limit);
}

function renderSharePage({ origin, type, id, item }) {
  const isTv = type === 'tv';
  const name = text(item?.title || item?.name) || (isTv ? 'TV show' : 'Movie');
  const date = item?.release_date || item?.first_air_date || '';
  const year = /^\d{4}/.test(date) ? date.slice(0, 4) : '';
  const title = (year ? name + ' (' + year + ')' : name) + ' | Reel';
  const overview = text(item?.overview, 230);
  const description = overview
    ? 'Watch on Reel. ' + overview
    : 'Watch ' + name + ' on Reel. Discover movies and TV shows in an interactive poster wall.';
  const imagePath = item?.backdrop_path || item?.poster_path || '';
  const imageSize = item?.backdrop_path ? 'w1280' : 'w780';
  const image = imagePath ? IMAGE_BASE + imageSize + imagePath : '';
  const imageAlt = name + (year ? ' (' + year + ')' : '') + (isTv ? ' TV show artwork' : ' movie artwork');
  const canonical = new URL('/' + type + '/' + id + '/', origin).href;
  const kind = isTv ? 'video.tv_show' : 'video.movie';
  const genres = Array.isArray(item?.genres) ? item.genres.map(genre => genre.name).filter(Boolean) : [];

  const tags = [
    '<!doctype html>',
    '<html lang="en">',
    '<head>',
    '<meta charset="utf-8">',
    '<meta name="viewport" content="width=device-width, initial-scale=1">',
    '<meta name="robots" content="index,follow">',
    '<title>' + escapeHtml(title) + '</title>',
    '<meta name="description" content="' + escapeHtml(description) + '">',
    '<link rel="canonical" href="' + escapeHtml(canonical) + '">',
    '<meta property="og:type" content="' + kind + '">',
    '<meta property="og:site_name" content="Reel">',
    '<meta property="og:title" content="' + escapeHtml(title) + '">',
    '<meta property="og:description" content="' + escapeHtml(description) + '">',
    '<meta property="og:url" content="' + escapeHtml(canonical) + '">',
    image ? '<meta property="og:image" content="' + escapeHtml(image) + '">' : '',
    image ? '<meta property="og:image:secure_url" content="' + escapeHtml(image) + '">' : '',
    image ? '<meta property="og:image:alt" content="' + escapeHtml(imageAlt) + '">' : '',
    image ? '<meta property="og:image:width" content="' + (item?.backdrop_path ? '1280' : '780') + '">' : '',
    image ? '<meta property="og:image:height" content="' + (item?.backdrop_path ? '720' : '1170') + '">' : '',
    '<meta name="twitter:card" content="' + (image ? 'summary_large_image' : 'summary') + '">',
    '<meta name="twitter:title" content="' + escapeHtml(title) + '">',
    '<meta name="twitter:description" content="' + escapeHtml(description) + '">',
    image ? '<meta name="twitter:image" content="' + escapeHtml(image) + '">' : '',
    image ? '<meta name="twitter:image:alt" content="' + escapeHtml(imageAlt) + '">' : '',
    '<meta name="twitter:label1" content="Watch on">',
    '<meta name="twitter:data1" content="Reel">',
    genres.length ? '<meta name="video:tag" content="' + escapeHtml(genres.slice(0, 5).join(', ')) + '">' : '',
    '</head>',
    '<body style="margin:0;background:#0a0a0a;color:#f5f5f5;font:16px/1.5 Arial,sans-serif">',
    '<main style="max-width:560px;margin:12vh auto;padding:24px">',
    '<p style="color:#a3a3a3">REEL</p>',
    '<h1>' + escapeHtml(name) + '</h1>',
    '<p>' + escapeHtml(description) + '</p>',
    '<p><a href="/" style="color:#f5f5f5">Watch on Reel</a></p>',
    '</main>',
    '<script>',
    '(async function(){',
    'try{',
    'const response=await fetch("/index.html",{cache:"no-store"});',
    'if(!response.ok)throw new Error("Could not load Reel");',
    'const app=await response.text();',
    'document.open();document.write(app);document.close();',
    '}catch(error){console.error(error);}',
    '})();',
    '</script>',
    '</body>',
    '</html>'
  ].filter(Boolean);

  return tags.join('\n');
}

export default async (request, context) => {
  const url = new URL(request.url);
  const segments = url.pathname.split('/').filter(Boolean);
  const type = segments[0] === 'tv' ? 'tv' : 'movie';
  const id = String(context?.params?.id || segments[1] || '');

  if (!/^\d+$/.test(id)) {
    return new Response('Invalid Reel share link.', {
      status: 400,
      headers: { 'Content-Type': 'text/plain; charset=utf-8' }
    });
  }

  let item = null;
  const apiKey = process.env.TMDB_API_KEY;
  if (apiKey) {
    try {
      const endpoint = new URL(TMDB_BASE + '/' + type + '/' + encodeURIComponent(id));
      endpoint.searchParams.set('api_key', apiKey);
      endpoint.searchParams.set('language', 'en-US');
      const response = await fetch(endpoint, {
        headers: { Accept: 'application/json' },
        signal: AbortSignal.timeout(10000)
      });
      if (response.ok) item = await response.json();
    } catch (error) {
      console.error('Reel share metadata lookup failed:', error);
    }
  }

  const html = renderSharePage({ origin: url.origin, type, id, item });
  const headers = {
    'Content-Type': 'text/html; charset=utf-8',
    'Cache-Control': 'public, max-age=300, s-maxage=1800, stale-while-revalidate=3600',
    'X-Content-Type-Options': 'nosniff',
    'Referrer-Policy': 'strict-origin-when-cross-origin'
  };
  return new Response(request.method === 'HEAD' ? null : html, {
    status: item ? 200 : 200,
    headers
  });
};
