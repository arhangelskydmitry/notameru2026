import type { Bot } from "grammy";
import { logger } from "../logger.js";
import type { ConversationLogStore } from "../chat/conversationLog.js";

const STARTUP_MESSAGE =
  "@dm9998111 @osipovalena\n\nАгент перезапущен и готов к работе.";

export async function notifyAgentRestarted(
  bot: Bot,
  conversationLog: ConversationLogStore
): Promise<void> {
  const chatIds = conversationLog.listChatIds();

  if (chatIds.length === 0) {
    logger.info("No known Telegram chats for startup notification");
    return;
  }

  for (const chatId of chatIds) {
    try {
      await bot.api.sendMessage(Number(chatId), STARTUP_MESSAGE);
      conversationLog.append({
        chatId,
        direction: "out",
        kind: "text",
        text: STARTUP_MESSAGE
      });
      logger.info({ chatId }, "Sent agent restart notification");
    } catch (error) {
      logger.error({ chatId, error }, "Failed to send agent restart notification");
    }
  }
}
