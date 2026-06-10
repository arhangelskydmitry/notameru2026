import type { AppConfig } from "../config.js";

export function isAllowedTelegramUser(
  userId: number | undefined,
  username: string | undefined,
  config: Pick<AppConfig, "allowedTelegramUserIds" | "allowedTelegramUsernames">
): boolean {
  if (userId !== undefined && config.allowedTelegramUserIds.includes(userId)) {
    return true;
  }

  if (username) {
    return config.allowedTelegramUsernames.includes(username.toLowerCase());
  }

  return false;
}
