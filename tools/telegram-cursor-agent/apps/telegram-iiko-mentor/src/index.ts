import { config } from "./config.js";
import { logger } from "./logger.js";
import { createTelegramBot } from "./bot/bot.js";
import { createServer } from "./server/app.js";
import { PersistentTaskStore } from "./tasks/taskQueue.js";
import { AgentRunner } from "./agent/agentRunner.js";
import { EventLog } from "./storage/eventLog.js";
import { ConversationLogStore } from "./chat/conversationLog.js";
import { IikoControlClient } from "./iikoControl/client.js";
import { notifyAgentRestarted } from "./bot/startupNotifier.js";
import { DialogModeStore } from "./dialog/dialogModeStore.js";

const startedAt = new Date();
const eventLog = new EventLog(config.eventLogFile);
const store = new PersistentTaskStore(config.stateFile);
const agentRunner = new AgentRunner(store, config, eventLog);
const conversationLog = new ConversationLogStore(config.conversationLogDirectory);
const iikoControl = new IikoControlClient(config.iikoControlApiBaseUrl);
const dialogMode = new DialogModeStore(config.dialogModeFile);
const bot = createTelegramBot({
  config,
  store,
  agentRunner,
  eventLog,
  conversationLog,
  iikoControl,
  dialogMode,
  startedAt
});
const server = createServer({ config, store, agentRunner, startedAt });

async function main(): Promise<void> {
  await server.listen({ host: config.host, port: config.port });
  logger.info(
    { host: config.host, port: config.port, workingDirectory: config.workingDirectory },
    "IIKO mentor HTTP server started"
  );

  bot.start({
    onStart: (botInfo) => {
      logger.info({ username: botInfo.username }, "IIKO mentor Telegram bot started");
      void notifyAgentRestarted(bot, conversationLog);
    }
  });
}

async function shutdown(signal: NodeJS.Signals): Promise<void> {
  logger.info({ signal }, "Shutting down IIKO mentor");
  bot.stop();
  await server.close();
}

process.once("SIGINT", () => {
  void shutdown("SIGINT");
});

process.once("SIGTERM", () => {
  void shutdown("SIGTERM");
});

main().catch((error) => {
  logger.error({ error }, "IIKO mentor failed to start");
  process.exitCode = 1;
});
