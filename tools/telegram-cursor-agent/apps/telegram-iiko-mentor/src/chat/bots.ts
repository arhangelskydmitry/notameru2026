export function normalizeBotUsername(username: string): string {
  return username.replace(/^@/, "").toLowerCase();
}

export function mentionsBot(input: string, botUsername: string): boolean {
  const normalized = normalizeBotUsername(botUsername);
  return new RegExp(`@${normalized}\\b`, "i").test(input);
}

export function isAddressedToBot(
  input: string,
  botUsername: string,
  isPrivateChat: boolean
): boolean {
  if (isPrivateChat) {
    return true;
  }

  return mentionsBot(input, botUsername);
}
