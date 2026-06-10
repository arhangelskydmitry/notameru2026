import type { ConversationStep } from "@cursor/sdk";

function shortPath(value: string | undefined): string | undefined {
  if (!value) {
    return undefined;
  }

  const parts = value.split("/");
  return parts[parts.length - 1] || value;
}

export function formatAgentStepStatus(step: ConversationStep): string | undefined {
  if (step.type === "thinkingMessage") {
    return "Разбираю вопрос и готовлю ответ...";
  }

  if (step.type === "assistantMessage") {
    return "Формирую ответ...";
  }

  if (step.type !== "toolCall") {
    return undefined;
  }

  const tool = step.message;

  switch (tool.type) {
    case "grep":
      return tool.args.pattern
        ? `Ищу в продукте: ${tool.args.pattern}`
        : "Ищу нужные места в проекте...";
    case "glob":
      return tool.args.globPattern
        ? `Просматриваю файлы: ${tool.args.globPattern}`
        : "Просматриваю файлы проекта...";
    case "read":
      return shortPath(tool.args.path)
        ? `Читаю ${shortPath(tool.args.path)}`
        : "Читаю документацию и код продукта...";
    default:
      return "Продолжаю разбор...";
  }
}

export const AGENT_TASK_STATUS = {
  accepted: "Принял вопрос, начинаю разбор...",
  finishing: "Завершаю, готовлю ответ...",
  done: "Готово, отправляю результат..."
} as const;
