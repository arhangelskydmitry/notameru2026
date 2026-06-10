import { isAddressedToBot, mentionsBot } from "./bots.js";
import { isPartnerAddressingPartner } from "./partners.js";

export type MessageDecision =
  | { type: "run"; normalizedInput: string }
  | { type: "clarify"; question: string }
  | { type: "chat"; response: string }
  | { type: "observe" };

const productPatterns = [
  /iiko|айко|ресторан|отчет|отчёт|экран|интерфейс|контроль|инцидент|скидк|возврат|касс|смен/i,
  /провер|тест|сценар|чек.?лист|демо|как\s+работает|объясни|покажи|что\s+значит/i,
  /ux|ui|удобн|понятн|непонятн|баг|ошибк|не\s+работает/i
];

const conversationalPatterns = [
  /привет|доброе\s+утро|добрый\s+день|добрый\s+вечер/i,
  /как\s+(твой\s+)?статус/i,
  /как\s+тебя\s+зовут|напомни.*зовут/i
];

interface DecideMessageOptions {
  senderUsername?: string;
  isPrivateChat?: boolean;
  mentorBotUsername?: string;
  controllerBotUsername?: string;
  dialogModeActive?: boolean;
}

export function decideMessage(
  input: string,
  options: DecideMessageOptions = {}
): MessageDecision {
  const normalizedInput = input.trim();
  const mentorBotUsername = options.mentorBotUsername ?? "ElenaPetrovnaMentor_bot";
  const controllerBotUsername = options.controllerBotUsername ?? "CursorLenaPetrovna_bot";
  const isPrivateChat = options.isPrivateChat ?? false;

  if (!normalizedInput) {
    return {
      type: "clarify",
      question: "Я не увидел текст. Напишите, что проверить или объяснить по iiko Control."
    };
  }

  if (
    mentionsBot(normalizedInput, controllerBotUsername) &&
    !mentionsBot(normalizedInput, mentorBotUsername)
  ) {
    return { type: "observe" };
  }

  if (
    isPartnerAddressingPartner(options.senderUsername, normalizedInput) &&
    !productPatterns.some((pattern) => pattern.test(normalizedInput))
  ) {
    return { type: "observe" };
  }

  if (options.dialogModeActive) {
    return { type: "observe" };
  }

  if (!isAddressedToBot(normalizedInput, mentorBotUsername, isPrivateChat)) {
    return { type: "observe" };
  }

  if (normalizedInput.length < 12) {
    if (conversationalPatterns.some((pattern) => pattern.test(normalizedInput))) {
      return {
        type: "chat",
        response:
          "Я на связи. Меня зовут Наставник IIKO — помогаю тестировать iiko Control и объяснять всё простым языком."
      };
    }

    return {
      type: "clarify",
      question:
        "Уточните, пожалуйста: что именно проверить, протестировать или объяснить по iiko Control?"
    };
  }

  return { type: "run", normalizedInput };
}
