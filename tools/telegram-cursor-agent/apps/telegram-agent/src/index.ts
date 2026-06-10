import { config } from "./config.js";
import { logger } from "./logger.js";
import { createTelegramBot } from "./bot/bot.js";
import { createServer } from "./server/app.js";
import { PersistentTaskStore } from "./tasks/taskQueue.js";
import { TaskRunner } from "./tasks/taskRunner.js";
import { AgentRunner } from "./agent/agentRunner.js";
import { ApprovalStore } from "./approvals/approvalStore.js";
import { EventLog } from "./storage/eventLog.js";
import { ChatMemoryStore } from "./chat/chatMemory.js";
import { ConversationLogStore } from "./chat/conversationLog.js";
import { SessionStore } from "./collab/sessionStore.js";
import { KanbanStore } from "./collab/kanbanStore.js";
import { DialogModeStore } from "./collab/dialogModeStore.js";
import { IikoMentorClient } from "./mentor/client.js";
import { BackupService } from "./backup/backupService.js";

const startedAt = new Date();
const eventLog = new EventLog(config.eventLogFile);
const store = new PersistentTaskStore(config.stateFile);
const runner = new TaskRunner(store, config);
const backups = new BackupService(config.workingDirectory, config.backupDirectory);
const agentRunner = new AgentRunner(store, config, eventLog, backups);
const approvals = new ApprovalStore(config.approvalsFile);
const chatMemory = new ChatMemoryStore(config.memoryFile);
const conversationLog = new ConversationLogStore(config.conversationLogDirectory);
const sessions = new SessionStore(config.sessionsFile, config.decisionsFile);
const kanban = new KanbanStore(config.kanbanFile);
const dialogMode = new DialogModeStore(config.dialogModeFile);
const mentorClient = new IikoMentorClient(config.iikoMentorApiBaseUrl);
const bot = createTelegramBot({
  config,
  store,
  runner,
  agentRunner,
  backups,
  approvals,
  eventLog,
  chatMemory,
  conversationLog,
  sessions,
  kanban,
  dialogMode,
  mentorClient,
  startedAt
});
const server = createServer({ config, store, startedAt });

async function main(): Promise<void> {
  await server.listen({ host: config.host, port: config.port });
  logger.info(
    { host: config.host, port: config.port, workingDirectory: config.workingDirectory },
    "HTTP server started"
  );

  bot.start({
    onStart: (botInfo) => {
      logger.info({ username: botInfo.username }, "Telegram bot started");
    }
  });
}

async function shutdown(signal: NodeJS.Signals): Promise<void> {
  logger.info({ signal }, "Shutting down");
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
  logger.error({ error }, "Application failed to start");
  process.exitCode = 1;
});
