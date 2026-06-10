import OpenAI, { toFile } from "openai";
import type { AppConfig } from "../config.js";

interface TranscribeVoiceOptions {
  config: Pick<AppConfig, "telegramBotToken" | "openAiApiKey" | "voiceTranscriptionModel">;
  filePath: string;
}

export async function transcribeTelegramVoice(
  options: TranscribeVoiceOptions
): Promise<string> {
  const { config, filePath } = options;

  if (!config.openAiApiKey) {
    throw new Error("Voice transcription is not configured: OPENAI_API_KEY is missing");
  }

  const fileUrl = `https://api.telegram.org/file/bot${config.telegramBotToken}/${filePath}`;
  const response = await fetch(fileUrl);

  if (!response.ok) {
    throw new Error(`Failed to download Telegram voice file: HTTP ${response.status}`);
  }

  const audioBuffer = Buffer.from(await response.arrayBuffer());
  const openai = new OpenAI({ apiKey: config.openAiApiKey });
  const transcription = await openai.audio.transcriptions.create({
    file: await toFile(audioBuffer, "voice.ogg", { type: "audio/ogg" }),
    model: config.voiceTranscriptionModel
  });

  return transcription.text.trim();
}
