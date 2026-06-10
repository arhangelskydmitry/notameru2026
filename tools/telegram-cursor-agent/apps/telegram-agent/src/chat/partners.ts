export const PARTNER_USERNAMES = ["dm9998111", "osipovalena"] as const;

export type PartnerUsername = (typeof PARTNER_USERNAMES)[number];

export function normalizeUsername(username: string | undefined): string | undefined {
  return username?.replace(/^@/, "").toLowerCase();
}

export function mentionsPartner(input: string, partner: string): boolean {
  const normalized = partner.replace(/^@/, "").toLowerCase();
  return new RegExp(`@${normalized}\\b`, "i").test(input);
}

export function isPartnerAddressingPartner(
  senderUsername: string | undefined,
  input: string
): boolean {
  const sender = normalizeUsername(senderUsername);

  if (!sender || !PARTNER_USERNAMES.includes(sender as PartnerUsername)) {
    return false;
  }

  return PARTNER_USERNAMES.some(
    (partner) => partner !== sender && mentionsPartner(input, partner)
  );
}
