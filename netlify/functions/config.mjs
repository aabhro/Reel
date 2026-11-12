function configuredHostUrl() {
  const configured = String(process.env.HOST_URL || '').trim();
  if (!configured) return '';
  try {
    const parsed = new URL(configured);
    return ['http:', 'https:'].includes(parsed.protocol) ? parsed.origin : '';
  } catch {
    return '';
  }
}

export default async () => {
  const supabaseUrl = process.env.SUPABASE_URL || '';
  const supabaseAnonKey = process.env.SUPABASE_ANON_KEY || process.env.SUPABASE_PUBLISHABLE_KEY || '';
  return Response.json({
    supabaseUrl,
    supabaseAnonKey,
    hostUrl: configuredHostUrl(),
    authConfigured: Boolean(supabaseUrl && supabaseAnonKey)
  }, {
    headers: {
      'Cache-Control': 'no-store',
      'Content-Type': 'application/json; charset=utf-8',
      'X-Content-Type-Options': 'nosniff'
    }
  });
};
