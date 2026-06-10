import "dotenv/config";
import path from "node:path";
import { z } from "zod";

const envSchema = z.object({
  TELEGRAM_BOT_TOKEN: z.string().min(1, "TELEGRAM_BOT_TOKEN is required"),
  ALLOWED_TELEGRAM_USER_IDS: z.string().default(""),
  ALLOWED_TELEGRAM_USERNAMES: z.string().default(""),
  HOST: z.string().default("0.0.0.0"),
  PORT: z.coerce.number().int().positive().default(3001),
  PUBLIC_BASE_URL: z.string().url().default("https://notame.pro"),
  WORKING_DIRECTORY: z.string().default(process.cwd()),
  IIKO_MENTOR_SHARED_DIR: z.string().optional(),
  IIKO_MENTOR_STATE_FILE: z.string().optional(),
  IIKO_MENTOR_LOG_FILE: z.string().optional(),
  IIKO_MENTOR_CONVERSATION_LOG_DIR: z.string().optional(),
  IIKO_MENTOR_CHECKLIST_FILE: z.string().optional(),
  TELEGRAM_POLL_INTERVAL_MS: z.coerce.number().int().positive().default(2_500),
  LOG_TAIL_CHARS: z.coerce.number().int().positive().default(3_500),
  OPENAI_API_KEY: z.string().optional(),
  VOICE_TRANSCRIPTION_MODEL: z.string().default("whisper-1"),
  MAX_VOICE_DURATION_SECONDS: z.coerce.number().int().positive().default(180),
  CURSOR_API_KEY: z.string().optional(),
  CURSOR_MODEL: z.string().default("composer-2.5"),
  MAX_AGENT_PROMPT_CHARS: z.coerce.number().int().positive().default(6_000),
  AGENT_RUN_TIMEOUT_MS: z.coerce.number().int().positive().default(180_000),
  IIKO_CONTROL_API_BASE_URL: z.string().url().default("http://127.0.0.1:4200"),
  CONTROLLER_BOT_USERNAME: z.string().default("CursorLenaPetrovna_bot"),
  MENTOR_BOT_USERNAME: z.string().default("ElenaPetrovnaMentor_bot"),
  IIKO_MENTOR_DIALOG_MODE_FILE: z.string().optional()
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

if (allowedTelegramUserIds.length === 0 && allowedTelegramUsernames.length === 0) {
  throw new Error(
    "Invalid environment configuration: set at least one ALLOWED_TELEGRAM_USER_IDS or ALLOWED_TELEGRAM_USERNAMES value"
  );
}

const workingDirectory = path.resolve(parsedEnv.data.WORKING_DIRECTORY);
const sharedDirectory = path.resolve(
  parsedEnv.data.IIKO_MENTOR_SHARED_DIR ?? path.join(workingDirectory, "..", "shared")
);

export const config = {
  telegramBotToken: parsedEnv.data.TELEGRAM_BOT_TOKEN,
  allowedTelegramUserIds,
  allowedTelegramUsernames,
  host: parsedEnv.data.HOST,
  port: parsedEnv.data.PORT,
  publicBaseUrl: parsedEnv.data.PUBLIC_BASE_URL,
  workingDirectory,
  sharedDirectory,
  stateFile: path.resolve(
    parsedEnv.data.IIKO_MENTOR_STATE_FILE ??
      path.join(sharedDirectory, "telegram-iiko-mentor-state.json")
  ),
  eventLogFile: path.resolve(
    parsedEnv.data.IIKO_MENTOR_LOG_FILE ??
      path.join(sharedDirectory, "telegram-iiko-mentor-log.jsonl")
  ),
  conversationLogDirectory: path.resolve(
    parsedEnv.data.IIKO_MENTOR_CONVERSATION_LOG_DIR ??
      path.join(sharedDirectory, "telegram-iiko-mentor-conversations")
  ),
  checklistFile: path.resolve(
    parsedEnv.data.IIKO_MENTOR_CHECKLIST_FILE ??
      path.join(workingDirectory, "docs", "iiko-testing-checklist.md")
  ),
  telegramPollIntervalMs: parsedEnv.data.TELEGRAM_POLL_INTERVAL_MS,
  logTailChars: parsedEnv.data.LOG_TAIL_CHARS,
  openAiApiKey: parsedEnv.data.OPENAI_API_KEY,
  voiceTranscriptionModel: parsedEnv.data.VOICE_TRANSCRIPTION_MODEL,
  maxVoiceDurationSeconds: parsedEnv.data.MAX_VOICE_DURATION_SECONDS,
  cursorApiKey: parsedEnv.data.CURSOR_API_KEY,
  cursorModel: parsedEnv.data.CURSOR_MODEL,
  maxAgentPromptChars: parsedEnv.data.MAX_AGENT_PROMPT_CHARS,
  agentRunTimeoutMs: parsedEnv.data.AGENT_RUN_TIMEOUT_MS,
  iikoControlApiBaseUrl: parsedEnv.data.IIKO_CONTROL_API_BASE_URL,
  controllerBotUsername: parsedEnv.data.CONTROLLER_BOT_USERNAME,
  mentorBotUsername: parsedEnv.data.MENTOR_BOT_USERNAME,
  dialogModeFile: path.resolve(
    parsedEnv.data.IIKO_MENTOR_DIALOG_MODE_FILE ??
      path.join(sharedDirectory, "telegram-dialog-mode.json")
  )
} as const;

export type AppConfig = typeof config;
