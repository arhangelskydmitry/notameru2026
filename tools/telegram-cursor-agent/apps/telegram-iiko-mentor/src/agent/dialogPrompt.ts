export interface DialogAgentResponse {
  task: string;
  done: boolean;
  summary: string;
}

const RESPONSE_FORMAT = [
  "Ответь строго в таком формате (без лишнего текста до и после):",
  "TASK: <одна конкретная задача для Контролера или ПУСТО>",
  "DONE: да|нет",
  "SUMMARY: <краткий вывод простым языком>"
].join("\n");

export function buildDialogPlanPrompt(input: string): string {
  return [
    "Ты Наставник IIKO в режиме совместной работы /диалог с Контролером.",
    "Твоя роль: анализировать проект iiko Control, ставить Контролеру одну конкретную задачу на разработку или тестирование.",
    "Ты НЕ разработчик. Не меняй код и файлы — только читай проект и документацию.",
    "Контролер (@CursorLenaPetrovna_bot) — единственный, кто вносит изменения в код.",
    "Смотри docs/iiko-testing-checklist.md, docs/product-decisions.md и текущее состояние проекта.",
    "Задача должна быть выполнима за один шаг, с понятным результатом.",
    "Если цель диалога достигнута или следующий шаг не нужен — DONE: да и TASK: ПУСТО.",
    "",
    RESPONSE_FORMAT,
    "",
    input
  ].join("\n");
}

export function buildDialogReviewPrompt(input: string): string {
  return [
    "Ты Наставник IIKO в режиме совместной работы /диалог с Контролером.",
    "Контролер выполнил задачу. Проверь результат: что сделано, что осталось, есть ли замечания.",
    "Ты НЕ разработчик. Не меняй код — только оценивай и формулируй следующий шаг.",
    "Если нужна ещё одна задача — DONE: нет и TASK: <следующая задача>.",
    "Если на этом этапе достаточно — DONE: да и TASK: ПУСТО.",
    "SUMMARY пиши простым языком для Димы и Елены Петровны.",
    "",
    RESPONSE_FORMAT,
    "",
    input
  ].join("\n");
}

export function parseDialogAgentResponse(raw: string): DialogAgentResponse {
  const taskMatch = raw.match(/^TASK:\s*(.*)$/im);
  const doneMatch = raw.match(/^DONE:\s*(да|нет|yes|no|true|false)/im);
  const summaryMatch = raw.match(/^SUMMARY:\s*(.*)$/im);

  const taskRaw = taskMatch?.[1]?.trim() ?? "";
  const task =
    !taskRaw ||
    /^пусто$/i.test(taskRaw) ||
    /^none$/i.test(taskRaw) ||
    /^-$/.test(taskRaw)
      ? ""
      : taskRaw;

  const doneValue = doneMatch?.[1]?.toLowerCase() ?? "";
  const done =
    doneValue === "да" ||
    doneValue === "yes" ||
    doneValue === "true" ||
    (!task && !doneMatch);

  const summary = summaryMatch?.[1]?.trim() || raw.trim().slice(0, 500);

  return { task, done, summary };
}
