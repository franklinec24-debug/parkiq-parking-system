const crypto = require('node:crypto');

const SESSION_COOKIE = 'parkiq_session';
const FLASH_COOKIE = 'parkiq_flash';
const SESSION_TTL_MS = 7 * 24 * 60 * 60 * 1000;

function getSecret() {
  if (process.env.SESSION_SECRET) return process.env.SESSION_SECRET;
  // Derive deterministic fallback from DATABASE_URL only for local dev when SESSION_SECRET is unset
  const seed = process.env.DATABASE_URL || 'local-dev-parkiq-secret';
  return crypto.createHash('sha256').update(seed).digest('hex');
}

function signPayload(obj) {
  const data = Buffer.from(JSON.stringify(obj), 'utf8').toString('base64url');
  const sig = crypto.createHmac('sha256', getSecret()).update(data).digest('base64url');
  return `${data}.${sig}`;
}

function verifyPayload(token) {
  if (!token || typeof token !== 'string') return null;
  const parts = token.split('.');
  if (parts.length !== 2) return null;
  const [data, sig] = parts;
  const expected = crypto.createHmac('sha256', getSecret()).update(data).digest('base64url');
  const sigBuf = Buffer.from(sig);
  const expBuf = Buffer.from(expected);
  if (sigBuf.length !== expBuf.length || !crypto.timingSafeEqual(sigBuf, expBuf)) {
    return null;
  }
  try {
    const parsed = JSON.parse(Buffer.from(data, 'base64url').toString('utf8'));
    if (parsed.exp && Date.now() > parsed.exp) return null;
    return parsed;
  } catch {
    return null;
  }
}

function parseCookies(req) {
  const raw = req.headers.cookie || '';
  const out = {};
  for (const part of raw.split(';')) {
    const idx = part.indexOf('=');
    if (idx <= 0) continue;
    const k = part.slice(0, idx).trim();
    const v = part.slice(idx + 1).trim();
    out[k] = decodeURIComponent(v);
  }
  return out;
}

function isSecureRequest(req) {
  return req.headers['x-forwarded-proto'] === 'https' || process.env.VERCEL === '1';
}

function cookieFlags(req, maxAgeSeconds) {
  const flags = ['Path=/', 'HttpOnly', 'SameSite=Lax'];
  if (typeof maxAgeSeconds === 'number') flags.push(`Max-Age=${maxAgeSeconds}`);
  if (isSecureRequest(req)) flags.push('Secure');
  return flags.join('; ');
}

function appendSetCookie(res, value) {
  const existing = res.getHeader('Set-Cookie');
  if (!existing) {
    res.setHeader('Set-Cookie', [value]);
  } else if (Array.isArray(existing)) {
    res.setHeader('Set-Cookie', [...existing, value]);
  } else {
    res.setHeader('Set-Cookie', [existing, value]);
  }
}

function getSession(req) {
  const cookies = parseCookies(req);
  return verifyPayload(cookies[SESSION_COOKIE]);
}

function setSession(req, res, user) {
  const payload = {
    user_id: user.id,
    name: user.name,
    email: user.email,
    phone: user.phone || '',
    role: user.role,
    exp: Date.now() + SESSION_TTL_MS,
  };
  const token = signPayload(payload);
  appendSetCookie(res, `${SESSION_COOKIE}=${encodeURIComponent(token)}; ${cookieFlags(req, Math.floor(SESSION_TTL_MS / 1000))}`);
}

function clearSession(req, res) {
  appendSetCookie(res, `${SESSION_COOKIE}=; ${cookieFlags(req, 0)}`);
}

function setFlash(req, res, type, message) {
  const token = signPayload({ type, message, exp: Date.now() + 5 * 60 * 1000 });
  appendSetCookie(res, `${FLASH_COOKIE}=${encodeURIComponent(token)}; ${cookieFlags(req, 300)}`);
}

function consumeFlash(req, res) {
  const cookies = parseCookies(req);
  if (!cookies[FLASH_COOKIE]) return null;
  const flash = verifyPayload(cookies[FLASH_COOKIE]);
  appendSetCookie(res, `${FLASH_COOKIE}=; ${cookieFlags(req, 0)}`);
  return flash;
}

function requireStaffPage(req, res, next) {
  const session = getSession(req);
  if (!session) return res.redirect('/index.php');
  if (session.role !== 'staff') return res.redirect('/user/dashboard.php');
  req.user = session;
  next();
}

function requireUserPage(req, res, next) {
  const session = getSession(req);
  if (!session) return res.redirect('/index.php');
  if (session.role !== 'user') return res.redirect('/staff/dashboard.php');
  req.user = session;
  next();
}

function requireAuthJson(req, res, next) {
  const session = getSession(req);
  if (!session) return res.status(401).json({ success: false, message: 'Authentication required.' });
  req.user = session;
  next();
}

function requireStaffJson(req, res, next) {
  const session = getSession(req);
  if (!session) return res.status(401).json({ success: false, message: 'Authentication required.' });
  if (session.role !== 'staff') return res.status(403).json({ success: false, message: 'Staff access required.' });
  req.user = session;
  next();
}

module.exports = {
  getSession,
  setSession,
  clearSession,
  setFlash,
  consumeFlash,
  requireStaffPage,
  requireUserPage,
  requireAuthJson,
  requireStaffJson,
};
