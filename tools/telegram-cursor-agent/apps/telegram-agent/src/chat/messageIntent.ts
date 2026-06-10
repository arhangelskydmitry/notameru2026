export type MessageDecision =
  | { type: "run"; normalizedInput: string }
  | { type: "approval_required"; normalizedInput: string; reason: string }
  | { type: "clarify"; question: string }
  | { type: "chat"; response: string };

const riskyPatterns = [
  { pattern: /\bdeploy\b|депло[ий]|разверн/i, reason: "это похоже на деплой или изменение работающего сервиса" },
  { pattern: /\bsudo\b|root|nginx|systemd|service/i, reason: "это затрагивает серверные службы или повышенные права" },
  { pattern: /\brm\b|удали|удалить|стереть|очисти/i, reason: "это может удалить данные или файлы" },
  { pattern: /secret|token|ключ|секрет|парол/i, reason: "это может затронуть секреты или доступы" },
  { pattern: /git\s+reset|git\s+clean|force|--force/i, reason: "это может необратимо изменить рабочее дерево" }
];

const developmentPatterns = [
  /сделай|добавь|исправь|обнови|создай|реализуй|подготовь|напиши|задокументируй/i,
  /bug|fix|implement|update|create|add|write|document|refactor/i,
  /notame|нота\s*миру|бот|сервер|api|документац|laravel|mac/i
];

const followUpPatterns = [
  /^(да|давай|ок|окей|конечно|согласен|согласна)$/i,
  /сделай\s+pdf/i,
  /pdf/i,
  /конверт/i,
  /собери/i
];

const conversationalPatterns = [
  /привет|доброе\s+утро|добрый\s+день|добрый\s+вечер/i,
  /как\s+(твой\s+)?статус/i,
  /как\s+тебя\s+зовут|напомни.*зовут/i
];

interface DecideMessageOptions {
  lastActionableContext?: string;
}

function withContext(input: string, context: string): string {
  return [
    "Продолжи предыдущий контекст чата.",
    "",
    "Предыдущий контекст:",
    context,
    "",
    "Новое сообщение:",
    input
  ].join("\n");
}

export function decideMessage(
  input: string,
  options: DecideMessageOptions = {}
): MessageDecision {
  const normalizedInput = input.trim();

  if (!normalizedInput) {
    return {
      type: "clarify",
      question: "Я не увидел текст задачи. Напишите, что нужно сделать."
    };
  }

  if (
    options.lastActionableContext &&
    followUpPatterns.some((pattern) => pattern.test(normalizedInput))
  ) {
    return {
      type: "run",
      normalizedInput: withContext(normalizedInput, options.lastActionableContext)
    };
  }

  if (normalizedInput.length < 12) {
    if (conversationalPatterns.some((pattern) => pattern.test(normalizedInput))) {
      return {
        type: "chat",
        response: "Я на связи. Меня можно использовать как основного агента проекта."
      };
    }

    return {
      type: "clarify",
      question: options.lastActionableContext
        ? "Я помню предыдущий контекст. Напишите чуть точнее, что именно сделать с ним, например: «сделай PDF», «добавь в docs» или «запусти это»."
        : "Уточните, пожалуйста, задачу чуть подробнее: что именно изменить или подготовить?"
    };
  }

  for (const riskyPattern of riskyPatterns) {
    if (riskyPattern.pattern.test(normalizedInput)) {
      return {
        type: "approval_required",
        normalizedInput,
        reason: riskyPattern.reason
      };
    }
  }

  if (developmentPatterns.some((pattern) => pattern.test(normalizedInput))) {
    return { type: "run", normalizedInput };
  }

  return { type: "run", normalizedInput };
}
