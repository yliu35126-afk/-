// utils/image.ts
// 统一图片URL解析与占位图兜底，三端一致治理
const config = require('../config/api.js');

/**
 * 解析图片URL：
 * - 支持绝对URL直接返回
 * - 相对路径使用当前资源域名拼接
 * - 本地/局域网域名替换为资源域名
 * - 为空时提供统一占位图
 */
export function resolveImageUrl(u: string | undefined | null, seed?: string | number): string {
  const raw = (u || '').trim();
  try {
    const normalized = config.normalizeUrl(raw);
    if (normalized) return normalized;
  } catch (_) {}

  // 统一占位图（同域名优先，避免小程序域名白名单问题）
  const domain = String(config.resourceDomain || '').replace(/\/$/, '');
  // 优先使用配置里的占位图路径
  const configured = (config.placeholders && config.placeholders.prizeImage) ? String(config.placeholders.prizeImage) : '';
  // 如果配置的是小程序包内资源（如 /images/...），直接返回该路径，避免拼接到资源域名
  if (configured && configured.startsWith('/images/')) {
    return configured;
  }
  const localPlaceholder = configured
    ? (configured.startsWith('http') ? configured : (domain ? `${domain}${configured.startsWith('/') ? '' : '/'}${configured}` : configured))
    : (domain ? `${domain}/assets/img/placeholder/prize.png` : '/images/success.svg');
  const seedStr = encodeURIComponent(String(seed || 'default'));
  // 若本地占位图不可用，则使用稳定的外部占位图
  return localPlaceholder || `https://picsum.photos/seed/${seedStr}/300/300`;
}

/**
 * 映射奖品对象的图片字段为标准 image，并完成URL解析与兜底。
 */
export function mapPrizeImage<T extends { [k: string]: any }>(prize: T): T {
  const src = prize.image || prize.pic_url || prize.prize_image || '';
  const resolved = resolveImageUrl(src, prize.id || prize.name || 'prize');
  return { ...prize, image: resolved };
}