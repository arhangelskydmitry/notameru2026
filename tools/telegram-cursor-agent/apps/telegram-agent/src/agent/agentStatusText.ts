import type { ConversationStep } from "@cursor/sdk";

function shortPath(value: string | undefined): string | undefined {
  if (!value) {
    return undefined;
  }

  const parts = value.split("/");
  return parts[parts.length - 1] || value;
}

function shortCommand(command: string): string {
  const firstLine = command.trim().split("\n")[0] ?? command;
  return firstLine.length > 60 ? `${firstLine.slice(0, 57)}...` : firstLine;
}

export function formatAgentStepStatus(step: ConversationStep): string | undefined {
  if (step.type === "thinkingMessage") {
    return "Разбираю задачу и продумываю план...";
  }

  if (step.type === "assistantMessage") {
    return "Формирую ответ...";
  }

  if (step.type !== "toolCall") {
    return undefined;
  }

  const tool = step.message;

  switch (tool.type) {
    case "shell":
      return `Запускаю команду: ${shortCommand(tool.args.command)}`;
    case "grep":
      return tool.args.pattern
        ? `Ищу в проекте: ${tool.args.pattern}`
        : "Ищу нужные места в коде...";
    case "glob":
      return tool.args.globPattern
        ? `Просматриваю файлы: ${tool.args.globPattern}`
        : "Просматриваю файлы проекта...";
    case "read":
      return shortPath(tool.args.path)
        ? `Читаю файл ${shortPath(tool.args.path)}`
        : "Читаю код проекта...";
    case "write":
      return shortPath(tool.args.path)
        ? `Вношу изменения в ${shortPath(tool.args.path)}`
        : "Вношу изменения в файлы...";
    case "delete":
      return shortPath(tool.args.path)
        ? `Удаляю файл ${shortPath(tool.args.path)}`
        : "Удаляю файл...";
    default:
      return "Продолжаю работу над задачей...";
  }
}

export const AGENT_TASK_STATUS = {
  accepted: "Принял задачу, начинаю работу...",
  finishing: "Завершаю, готовлю отчёт...",
  done: "Готово, отправляю результат..."
} as const;
