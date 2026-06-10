import "dotenv/config";
import path from "node:path";
import { z } from "zod";

const envSchema = z.object({
  TELEGRAM_BOT_TOKEN: z.string().min(1, "TELEGRAM_BOT_TOKEN is required"),
  ALLOWED_TELEGRAM_USER_IDS: z.string().default(""),
  ALLOWED_TELEGRAM_USERNAMES: z.string().default(""),
  TELEGRAM_APPROVER_USER_IDS: z.string().default(""),
  TELEGRAM_APPROVER_USERNAMES: z.string().default(""),
  HOST: z.string().default("0.0.0.0"),
  PORT: z.coerce.number().int().positive().default(3000),
  PUBLIC_BASE_URL: z.string().url().default("https://notame.ru"),
  WORKING_DIRECTORY: z.string().default(process.cwd()),
  TELEGRAM_AGENT_SHARED_DIR: z.string().optional(),
  TELEGRAM_AGENT_STATE_FILE: z.string().optional(),
  TELEGRAM_AGENT_LOG_FILE: z.string().optional(),
  TELEGRAM_AGENT_QUEUE_FILE: z.string().optional(),
  TELEGRAM_AGENT_APPROVALS_FILE: z.string().optional(),
  TELEGRAM_AGENT_MEMORY_FILE: z.string().optional(),
  TELEGRAM_AGENT_CONVERSATION_LOG_DIR: z.string().optional(),
  TELEGRAM_AGENT_BACKUP_DIR: z.string().optional(),
  TELEGRAM_AGENT_SESSIONS_FILE: z.string().optional(),
  TELEGRAM_AGENT_KANBAN_FILE: z.string().optional(),
  TELEGRAM_AGENT_DECISIONS_FILE: z.string().optional(),
  TELEGRAM_POLL_INTERVAL_MS: z.coerce.number().int().positive().default(2_500),
  CHAT_SILENCE_CHECK_INTERVAL_MS: z.coerce.number().int().positive().default(3_600_000),
  CHAT_SILENCE_THRESHOLD_MS: z.coerce.number().int().positive().default(3_600_000),
  COMMAND_TIMEOUT_MS: z.coerce.number().int().positive().default(120_000),
  LOG_TAIL_CHARS: z.coerce.number().int().positive().default(3_500),
  OPENAI_API_KEY: z.string().optional(),
  VOICE_TRANSCRIPTION_MODEL: z.string().default("whisper-1"),
  MAX_VOICE_DURATION_SECONDS: z.coerce.number().int().positive().default(180),
  CURSOR_API_KEY: z.string().optional(),
  CURSOR_MODEL: z.string().default("composer-2.5"),
  AUTO_AGENT_ON_CHAT: z.coerce.boolean().default(true),
  MAX_AGENT_PROMPT_CHARS: z.coerce.number().int().positive().default(6_000),
  AGENT_RUN_TIMEOUT_MS: z.coerce.number().int().positive().default(180_000),
  IIKO_MENTOR_API_BASE_URL: z.string().url().default("http://127.0.0.1:3001"),
  TELEGRAM_AGENT_DIALOG_MODE_FILE: z.string().optional()
});

function parseAllowedUserIds(value: string): number[] {
  return value
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean)
    .map((item) => Number(item))
    .filter((item) => Number.isSafeInteger(item) && item > 0);
}

function parseAllowedUsernames(value: string): string[] {
  return value
    .split(",")
    .map((item) => item.trim().replace(/^@/, "").toLowerCase())
    .filter(Boolean);
}

const parsedEnv = envSchema.safeParse(process.env);

if (!parsedEnv.success) {
  const message = parsedEnv.error.issues
    .map((issue) => `${issue.path.join(".")}: ${issue.message}`)
    .join("; ");

  throw new Error(`Invalid environment configuration: ${message}`);
}

const allowedTelegramUserIds = parseAllowedUserIds(
  parsedEnv.data.ALLOWED_TELEGRAM_USER_IDS
);
const allowedTelegramUsernames = parseAllowedUsernames(
  parsedEnv.data.ALLOWED_TELEGRAM_USERNAMES
);
const approverTelegramUserIds = parseAllowedUserIds(
  parsedEnv.data.TELEGRAM_APPROVER_USER_IDS
);
const approverTelegramUsernames = parseAllowedUsernames(
  parsedEnv.data.TELEGRAM_APPROVER_USERNAMES
);

if (allowedTelegramUserIds.length === 0 && allowedTelegramUsernames.length === 0) {
  throw new Error(
    "Invalid environment configuration: set at least one ALLOWED_TELEGRAM_USER_IDS or ALLOWED_TELEGRAM_USERNAMES value"
  );
}

const workingDirectory = path.resolve(parsedEnv.data.WORKING_DIRECTORY);
const sharedDirectory = path.resolve(
  parsedEnv.data.TELEGRAM_AGENT_SHARED_DIR ?? path.join(workingDirectory, "..", "shared")
);

export const config = {
  telegramBotToken: parsedEnv.data.TELEGRAM_BOT_TOKEN,
  allowedTelegramUserIds,
  allowedTelegramUsernames,
  approverTelegramUserIds:
    approverTelegramUserIds.length > 0 ? approverTelegramUserIds : allowedTelegramUserIds,
  approverTelegramUsernames:
    approverTelegramUsernames.length > 0
      ? approverTelegramUsernames
      : allowedTelegramUsernames,
  host: parsedEnv.data.HOST,
  port: parsedEnv.data.PORT,
  publicBaseUrl: parsedEnv.data.PUBLIC_BASE_URL,
  workingDirectory,
  sharedDirectory,
  stateFile: path.resolve(
    parsedEnv.data.TELEGRAM_AGENT_STATE_FILE ??
      path.join(sharedDirectory, "telegram-agent-state.json")
  ),
  eventLogFile: path.resolve(
    parsedEnv.data.TELEGRAM_AGENT_LOG_FILE ??
      path.join(sharedDirectory, "telegram-agent-log.jsonl")
  ),
  failedQueueFile: path.resolve(
    parsedEnv.data.TELEGRAM_AGENT_QUEUE_FILE ??
      path.join(sharedDirectory, "telegram-agent-queue.jsonl")
  ),
  approvalsFile: path.resolve(
    parsedEnv.data.TELEGRAM_AGENT_APPROVALS_FILE ??
      path.join(sharedDirectory, "telegram-agent-approvals.json")
  ),
  memoryFile: path.resolve(
    parsedEnv.data.TELEGRAM_AGENT_MEMORY_FILE ??
      path.join(sharedDirectory, "telegram-agent-memory.json")
  ),
  conversationLogDirectory: path.resolve(
    parsedEnv.data.TELEGRAM_AGENT_CONVERSATION_LOG_DIR ??
      path.join(sharedDirectory, "telegram-agent-conversations")
  ),
  backupDirectory: path.resolve(
    parsedEnv.data.TELEGRAM_AGENT_BACKUP_DIR ??
      path.join(sharedDirectory, "telegram-agent-backups")
  ),
  sessionsFile: path.resolve(
    parsedEnv.data.TELEGRAM_AGENT_SESSIONS_FILE ??
      path.join(sharedDirectory, "telegram-agent-sessions.json")
  ),
  kanbanFile: path.resolve(
    parsedEnv.data.TELEGRAM_AGENT_KANBAN_FILE ??
      path.join(sharedDirectory, "telegram-agent-kanban.json")
  ),
  decisionsFile: path.resolve(
    parsedEnv.data.TELEGRAM_AGENT_DECISIONS_FILE ??
      path.join(workingDirectory, "docs", "product-decisions.md")
  ),
  telegramPollIntervalMs: parsedEnv.data.TELEGRAM_POLL_INTERVAL_MS,
  chatSilenceCheckIntervalMs: parsedEnv.data.CHAT_SILENCE_CHECK_INTERVAL_MS,
  chatSilenceThresholdMs: parsedEnv.data.CHAT_SILENCE_THRESHOLD_MS,
  commandTimeoutMs: parsedEnv.data.COMMAND_TIMEOUT_MS,
  logTailChars: parsedEnv.data.LOG_TAIL_CHARS,
  openAiApiKey: parsedEnv.data.OPENAI_API_KEY,
  voiceTranscriptionModel: parsedEnv.data.VOICE_TRANSCRIPTION_MODEL,
  maxVoiceDurationSeconds: parsedEnv.data.MAX_VOICE_DURATION_SECONDS,
  cursorApiKey: parsedEnv.data.CURSOR_API_KEY,
  cursorModel: parsedEnv.data.CURSOR_MODEL,
  autoAgentOnChat: parsedEnv.data.AUTO_AGENT_ON_CHAT,
  maxAgentPromptChars: parsedEnv.data.MAX_AGENT_PROMPT_CHARS,
  agentRunTimeoutMs: parsedEnv.data.AGENT_RUN_TIMEOUT_MS,
  iikoMentorApiBaseUrl: parsedEnv.data.IIKO_MENTOR_API_BASE_URL,
  dialogModeFile: path.resolve(
    parsedEnv.data.TELEGRAM_AGENT_DIALOG_MODE_FILE ??
      path.join(sharedDirectory, "telegram-dialog-mode.json")
  )
} as const;

export type AppConfig = typeof config;
